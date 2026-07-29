<?php

require_once __DIR__ . '/campaign.php';
require_once __DIR__ . '/cookies.php';
require_once __DIR__ . '/logging.php';

const EXPERIMENT_SESSION_KEY = 'experiment_paths';

function experiment_decode_state(string $raw): array
{
    if ($raw === '') {
        return [];
    }
    $decodedRaw = base64_decode($raw, true);
    if ($decodedRaw === false) {
        $decodedRaw = $raw;
    }
    $decoded = json_decode($decodedRaw, true);
    return is_array($decoded) ? $decoded : [];
}

function experiment_session_state(): array
{
    $state = session_read(EXPERIMENT_SESSION_KEY);
    return is_array($state) ? $state : [];
}

function experiment_write_session_state(array $state): void
{
    session_write(EXPERIMENT_SESSION_KEY, $state);
}

function experiment_sticky_state(): array
{
    if (isset($GLOBALS['ytds_saved_paths_state'])
        && is_array($GLOBALS['ytds_saved_paths_state'])) {
        return $GLOBALS['ytds_saved_paths_state'];
    }
    $raw = isset($_COOKIE['saved_paths']) && is_string($_COOKIE['saved_paths'])
        ? $_COOKIE['saved_paths']
        : '';
    $state = experiment_decode_state($raw);
    $GLOBALS['ytds_saved_paths_state'] = $state;
    return $state;
}

function experiment_write_sticky_state(array $state): void
{
    $GLOBALS['ytds_saved_paths_state'] = $state;
    $json = json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json !== false) {
        set_cookie('saved_paths', base64_encode($json));
    }
}

function experiment_get_flow_path(
    array $state,
    int $campaignId,
    string $flowName,
    array $steps
): array {
    $path = $state[(string)$campaignId][$flowName]['path'] ?? [];
    if (!is_array($path) || !is_valid_planned_path($path, $steps)) {
        return [];
    }
    return array_values($path);
}

function experiment_session_flow_path(int $campaignId, string $flowName, array $steps): array
{
    return experiment_get_flow_path(
        experiment_session_state(),
        $campaignId,
        $flowName,
        $steps
    );
}

function experiment_sticky_flow_path(int $campaignId, string $flowName, array $steps): array
{
    return experiment_get_flow_path(
        experiment_sticky_state(),
        $campaignId,
        $flowName,
        $steps
    );
}

function experiment_save_flow_path(
    int $campaignId,
    string $flowName,
    array $path,
    bool $sticky
): void {
    $campaignKey = (string)$campaignId;
    $sessionState = experiment_session_state();
    $sessionState[$campaignKey][$flowName]['path'] = array_values($path);
    experiment_write_session_state($sessionState);

    if (!$sticky) {
        return;
    }
    $stickyState = experiment_sticky_state();
    $stickyState[$campaignKey][$flowName]['path'] = array_values($path);
    experiment_write_sticky_state($stickyState);
}

function experiment_assignment_from_state(
    array $state,
    int $campaignId,
    string $flowName,
    int $stepIndex,
    string $folderName
): ?array {
    $assignment = $state[(string)$campaignId][$flowName]['mvt'][(string)$stepIndex][$folderName]
        ?? null;
    return is_array($assignment) ? $assignment : null;
}

function mvt_assignment_entries_are_known(MvtSettings $settings, array $assignment): bool
{
    foreach ($assignment as $testNumber => $valueCode) {
        if (!is_string($testNumber) && !is_int($testNumber)) {
            return false;
        }
        $testIndex = (int)$testNumber - 1;
        if ($testIndex < 0 || !isset($settings->tests[$testIndex])) {
            return false;
        }
        if (!is_string($valueCode)) {
            return false;
        }
        $valueIndex = MvtSettings::valueIndex($valueCode);
        if ($valueIndex === null || !isset($settings->tests[$testIndex]->values[$valueIndex])) {
            return false;
        }
    }
    return true;
}

function mvt_pick_active_value(MvtTestSettings $test): ?string
{
    $activeIndexes = [];
    foreach ($test->values as $index => $value) {
        if ($value->active) {
            $activeIndexes[] = $index;
        }
    }
    if ($activeIndexes === []) {
        return null;
    }
    $selectedIndex = $activeIndexes[random_int(0, count($activeIndexes) - 1)];
    return MvtSettings::valueCode($selectedIndex);
}

function mvt_new_assignment(MvtSettings $settings): array
{
    $assignment = [];
    foreach ($settings->tests as $testIndex => $test) {
        if (!$test->active) {
            continue;
        }
        $valueCode = mvt_pick_active_value($test);
        if ($valueCode === null) {
            add_log(
                'warning',
                'MVT TEST' . ($testIndex + 1) . ' has no active Values and was skipped'
            );
            continue;
        }
        $assignment[(string)($testIndex + 1)] = $valueCode;
    }
    return $assignment;
}

function mvt_complete_saved_assignment(
    MvtSettings $settings,
    array $assignment
): ?array {
    if (!mvt_assignment_entries_are_known($settings, $assignment)) {
        return null;
    }
    foreach ($settings->tests as $testIndex => $test) {
        if (!$test->active) {
            continue;
        }
        $testNumber = (string)($testIndex + 1);
        if (array_key_exists($testNumber, $assignment)) {
            continue;
        }
        $valueCode = mvt_pick_active_value($test);
        if ($valueCode !== null) {
            $assignment[$testNumber] = $valueCode;
        }
    }
    uksort($assignment, static fn($left, $right): int => (int)$left <=> (int)$right);
    return $assignment;
}

function experiment_save_mvt_assignment(
    Campaign $campaign,
    FlowSettings $flow,
    int $stepIndex,
    string $folderName,
    array $assignment
): void {
    $campaignKey = (string)$campaign->campaignId;
    $stepKey = (string)$stepIndex;

    $sessionState = experiment_session_state();
    $sessionState[$campaignKey][$flow->name]['mvt'][$stepKey][$folderName] = $assignment;
    experiment_write_session_state($sessionState);

    if (!$campaign->saveUserFlow) {
        return;
    }
    $stickyState = experiment_sticky_state();
    $stickyState[$campaignKey][$flow->name]['mvt'][$stepKey][$folderName] = $assignment;
    experiment_write_sticky_state($stickyState);
}

function select_mvt_assignment(
    Campaign $campaign,
    FlowSettings $flow,
    int $stepIndex,
    string $folderName
): array {
    $settings = $flow->steps[$stepIndex]->getMvt($folderName) ?? null;
    if ($settings === null || !$settings->enabled) {
        return [];
    }

    $sources = [
        'session' => experiment_session_state(),
    ];
    if ($campaign->saveUserFlow) {
        $sources['sticky'] = experiment_sticky_state();
    }

    foreach ($sources as $sourceName => $state) {
        $saved = experiment_assignment_from_state(
            $state,
            $campaign->campaignId,
            $flow->name,
            $stepIndex,
            $folderName
        );
        if ($saved === null) {
            continue;
        }
        $completed = mvt_complete_saved_assignment($settings, $saved);
        if ($completed === null) {
            add_log(
                'warning',
                "Invalid MVT {$sourceName} assignment for campaign {$campaign->campaignId}, "
                . "flow {$flow->name}, step {$stepIndex}, landing {$folderName}"
            );
            break;
        }
        experiment_save_mvt_assignment(
            $campaign,
            $flow,
            $stepIndex,
            $folderName,
            $completed
        );
        return $completed;
    }

    $assignment = mvt_new_assignment($settings);
    experiment_save_mvt_assignment(
        $campaign,
        $flow,
        $stepIndex,
        $folderName,
        $assignment
    );
    return $assignment;
}

function apply_mvt_assignment(
    string $html,
    ?MvtSettings $settings,
    array $assignment
): string {
    if ($settings === null || !$settings->enabled || $assignment === []) {
        return $html;
    }
    if (!mvt_assignment_entries_are_known($settings, $assignment)) {
        return $html;
    }
    $search = [];
    $replace = [];
    foreach ($assignment as $testNumber => $valueCode) {
        $testIndex = (int)$testNumber - 1;
        $valueIndex = MvtSettings::valueIndex((string)$valueCode);
        if ($testIndex < 0 || $valueIndex === null) {
            continue;
        }
        $search[] = '#TEST' . ($testIndex + 1) . '#';
        $replace[] = $settings->tests[$testIndex]->values[$valueIndex]->value;
    }
    return str_replace($search, $replace, $html);
}
