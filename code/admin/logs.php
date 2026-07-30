<?php

require_once __DIR__ . '/securitycheck.php';
require_once __DIR__ . '/../logging.php';
require_once __DIR__ . '/../db/db.php';

$gs = $db->get_common_settings();
$tz = (string)($gs['statistics']['timezone'] ?? 'UTC');
if (!in_array($tz, timezone_identifiers_list(), true)) $tz = 'UTC';
date_default_timezone_set($tz);
$calendarStart = (string)($_GET['startdate'] ?? date('d.m.y'));
$calendarEnd = (string)($_GET['enddate'] ?? $calendarStart);
$startDate = DateTimeImmutable::createFromFormat('!d.m.y', $calendarStart, new DateTimeZone($tz));
$endDate = DateTimeImmutable::createFromFormat('!d.m.y', $calendarEnd, new DateTimeZone($tz));
$start = $startDate !== false && $startDate->format('d.m.y') === $calendarStart ? $startDate->format('Y-m-d') : '';
$end = $endDate !== false && $endDate->format('d.m.y') === $calendarEnd ? $endDate->format('Y-m-d') : '';
$view = (string)($_GET['view'] ?? 'server') === 'postbacks' ? 'postbacks' : 'server';
$levels = isset($_GET['levels']) && is_array($_GET['levels'])
    ? array_values(array_map('strval', $_GET['levels']))
    : ['info', 'warning', 'error'];
$postbackSources = ['postback.incoming', 'postback.outgoing'];
$sources = isset($_GET['sources']) && is_array($_GET['sources'])
    ? array_values(array_map('strval', $_GET['sources']))
    : [];
if ($view === 'postbacks') {
    $sources = array_values(array_intersect($postbackSources, $sources));
    if ($sources === []) {
        $sources = $postbackSources;
    }
}
$search = trim((string)($_GET['search'] ?? ''));
$cursor = isset($_GET['cursor']) ? (string)$_GET['cursor'] : null;
$reader = new YellowTdsLogReader(dirname(__DIR__));
$error = null;
$result = ['entries' => [], 'nextCursor' => null, 'malformed' => 0, 'counts' => array_fill_keys(YellowTdsLogger::LEVELS, 0), 'sources' => []];

try {
    $result = $reader->query($start, $end, $levels, $sources, $search, $cursor);
} catch (InvalidArgumentException $exception) {
    $error = $exception->getMessage();
}

if (($result['malformed'] ?? 0) > 0) {
    ytds_log('warning', 'logviewer', 'Malformed structured log records were skipped', ['count' => $result['malformed']]);
}

if (($_GET['download'] ?? '') === '1' && $error === null) {
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        exit('ZIP support is not available.');
    }
    $allEntries = [];
    $downloadCursor = null;
    do {
        $page = $reader->query($start, $end, $levels, $sources, $search, $downloadCursor);
        array_push($allEntries, ...$page['entries']);
        $downloadCursor = $page['nextCursor'];
        if (count($allEntries) >= 50000) {
            $allEntries = array_slice($allEntries, 0, 50000);
            break;
        }
    } while ($downloadCursor !== null);

    $lines = array_map(
        static fn(array $entry): string => (string)json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE),
        $allEntries
    );
    $temp = tempnam(sys_get_temp_dir(), 'ytds_logs_');
    $zip = new ZipArchive();
    if ($temp === false || $zip->open($temp, ZipArchive::OVERWRITE) !== true) {
        if ($temp !== false) @unlink($temp);
        http_response_code(500);
        exit('Unable to create the archive.');
    }
    $zip->addFromString('logs.jsonl', implode("\n", $lines) . ($lines === [] ? '' : "\n"));
    $zip->addFromString('filters.json', (string)json_encode([
        'start' => $start,
        'end' => $end,
        'levels' => $levels,
        'sources' => $sources,
        'search' => $search,
        'exportedRecords' => count($allEntries),
        'truncated' => count($allEntries) >= 50000,
        'generatedAt' => date('c'),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="YellowTDS-logs-' . $start . '-' . $end . '.zip"');
    header('Content-Length: ' . filesize($temp));
    readfile($temp);
    @unlink($temp);
    exit;
}

function logs_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function logs_format_timestamp(string $timestamp, string $timezone): string
{
    try {
        return (new DateTimeImmutable($timestamp))
            ->setTimezone(new DateTimeZone($timezone))
            ->format('Y-m-d H:i:s');
    } catch (Throwable) {
        return $timestamp;
    }
}

function logs_query(array $overrides = []): string
{
    $params = $_GET;
    unset($params['download']);
    foreach ($overrides as $key => $value) {
        if ($value === null) unset($params[$key]); else $params[$key] = $value;
    }
    return http_build_query($params);
}

function logs_source_label(string $source, string $view): string
{
    if ($view !== 'postbacks') {
        return $source;
    }
    return match ($source) {
        'postback.incoming' => 'Incoming',
        'postback.outgoing' => 'Outgoing',
        default => $source,
    };
}

function logs_postback_outcome_label(string $outcome): string
{
    return match ($outcome) {
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
        'delivered' => 'Delivered',
        'sent_unconfirmed' => 'Sent · response not checked',
        'failed' => 'Failed',
        default => str_replace('_', ' ', ucfirst($outcome)),
    };
}
?>
<!doctype html>
<html lang="en">
<?php include __DIR__ . '/head.php'; ?>
<link rel="stylesheet" href="<?=get_admin_base_url()?>css/logs.css?v=<?=filemtime(__DIR__ . '/css/logs.css')?>">
<body class="logs-page">
<?php include __DIR__ . '/header.php'; ?>
<main class="all-content-wrapper logs-content">
    <nav class="logs-tabs" aria-label="Log views">
        <a class="logs-tab<?= $view === 'server' ? ' active' : '' ?>" href="?<?=logs_h(http_build_query(['view' => 'server', 'startdate' => $calendarStart, 'enddate' => $calendarEnd]))?>"<?= $view === 'server' ? ' aria-current="page"' : '' ?>>
            <i class="bi bi-list-ul"></i> Server logs
        </a>
        <a class="logs-tab<?= $view === 'postbacks' ? ' active' : '' ?>" href="?<?=logs_h(http_build_query(['view' => 'postbacks', 'startdate' => $calendarStart, 'enddate' => $calendarEnd]))?>"<?= $view === 'postbacks' ? ' aria-current="page"' : '' ?>>
            <i class="bi bi-arrow-left-right"></i> Postbacks
        </a>
    </nav>
    <?php if ($view === 'postbacks'): ?>
        <p class="postback-log-note">
            Incoming requests show whether YellowTDS accepted them. Conversion S2S deliveries show the recipient HTTP result; event S2S keeps collection fast and reports a successful socket write as “response not checked”.
        </p>
    <?php endif; ?>
    <form class="logs-filters" method="get">
        <input type="hidden" name="view" value="<?=logs_h($view)?>">
        <input type="hidden" name="startdate" value="<?=logs_h($calendarStart)?>">
        <input type="hidden" name="enddate" value="<?=logs_h($calendarEnd)?>">
        <div class="logs-filter-top">
            <label class="logs-search"><span>Search</span><input class="form-control" type="search" name="search" value="<?=logs_h($search)?>" placeholder="<?= $view === 'postbacks' ? 'Click ID, status, URL or response' : 'Message or context' ?>"></label>
            <div class="logs-filter-actions">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel"></i> Apply</button>
                <a class="btn btn-outline-secondary" href="?<?=logs_h(http_build_query(['view' => $view, 'startdate' => $calendarStart, 'enddate' => $calendarEnd]))?>">Reset</a>
                <a class="btn btn-success" href="?<?=logs_h(logs_query(['download' => '1', 'cursor' => null]))?>"><i class="bi bi-download"></i> Download ZIP</a>
            </div>
        </div>
        <?php
        $availableSources = array_values(array_unique(array_merge($result['sources'] ?? [], $sources)));
        if ($view === 'postbacks') {
            $availableSources = array_values(array_intersect($postbackSources, $availableSources));
        }
        sort($availableSources);
        ?>
        <div class="logs-filter-bar">
            <div class="logs-chip-filter" data-chip-filter data-name="levels[]">
                <span class="logs-filter-label">Levels</span>
                <div class="logs-chip-row">
                    <div class="logs-chips" data-chip-list>
                        <?php foreach ($levels as $level): if (!in_array($level, YellowTdsLogger::LEVELS, true)) continue; ?>
                            <span class="logs-chip chip-<?=logs_h($level)?>" data-value="<?=logs_h($level)?>">
                                <span><?=ucfirst(logs_h($level))?> <small><?= (int)($result['counts'][$level] ?? 0) ?></small></span>
                                <button type="button" data-remove-chip aria-label="Remove <?=logs_h($level)?>">&times;</button>
                                <input type="hidden" name="levels[]" value="<?=logs_h($level)?>">
                            </span>
                        <?php endforeach; ?>
                        <span class="logs-chip-empty" data-chip-empty>All levels</span>
                    </div>
                    <select class="logs-chip-add" data-chip-add aria-label="Add log level">
                        <option value="">+ Add</option>
                        <?php foreach (YellowTdsLogger::LEVELS as $level): ?>
                            <option value="<?=logs_h($level)?>" data-label="<?=ucfirst(logs_h($level))?>" data-count="<?= (int)($result['counts'][$level] ?? 0) ?>"><?=ucfirst(logs_h($level))?> · <?= (int)($result['counts'][$level] ?? 0) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="logs-chip-filter" data-chip-filter data-name="sources[]">
                <span class="logs-filter-label">Sources</span>
                <div class="logs-chip-row">
                    <div class="logs-chips" data-chip-list>
                        <?php foreach ($sources as $source): if (!in_array($source, $availableSources, true)) continue; ?>
                            <span class="logs-chip chip-source" data-value="<?=logs_h($source)?>">
                                <span><?=logs_h(logs_source_label($source, $view))?></span>
                                <button type="button" data-remove-chip aria-label="Remove <?=logs_h(logs_source_label($source, $view))?>">&times;</button>
                                <input type="hidden" name="sources[]" value="<?=logs_h($source)?>">
                            </span>
                        <?php endforeach; ?>
                        <span class="logs-chip-empty" data-chip-empty>All sources</span>
                    </div>
                    <select class="logs-chip-add" data-chip-add aria-label="Add log source">
                        <option value="">+ Add</option>
                        <?php foreach ($availableSources as $source): ?><option value="<?=logs_h($source)?>" data-label="<?=logs_h(logs_source_label($source, $view))?>"><?=logs_h(logs_source_label($source, $view))?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </form>

    <?php if ($error !== null): ?><div class="alert alert-danger"><?=logs_h($error)?></div><?php endif; ?>
    <?php if (($result['malformed'] ?? 0) > 0): ?><div class="alert alert-warning"><?= (int)$result['malformed'] ?> malformed records were skipped.</div><?php endif; ?>

    <section class="logs-stream" aria-live="polite">
        <?php if ($error === null && $result['entries'] === []): ?><div class="logs-empty">No records match the selected filters.</div><?php endif; ?>
        <?php foreach ($result['entries'] as $entry): ?>
            <?php
            $entryContext = is_array($entry['context'] ?? null) ? $entry['context'] : [];
            $postbackOutcome = isset($entryContext['outcome']) ? (string)$entryContext['outcome'] : '';
            ?>
            <article class="log-entry log-<?=logs_h($entry['level'])?><?= $postbackOutcome !== '' ? ' postback-outcome-' . logs_h($postbackOutcome) : '' ?>">
                <div class="log-meta">
                    <time datetime="<?=logs_h($entry['timestamp'])?>"><?=logs_h(logs_format_timestamp((string)$entry['timestamp'], $tz))?></time>
                    <span class="log-level"><?=logs_h(strtoupper($entry['level']))?></span>
                    <span class="log-source"><?=logs_h(logs_source_label((string)$entry['source'], $view))?></span>
                    <?php if ($postbackOutcome !== ''): ?>
                        <span class="postback-outcome"><?=logs_h(logs_postback_outcome_label($postbackOutcome))?></span>
                    <?php endif; ?>
                    <?php if (isset($entryContext['http_code'])): ?>
                        <span class="postback-http">HTTP <?= (int)$entryContext['http_code'] ?></span>
                    <?php endif; ?>
                </div>
                <div class="log-message"><?=nl2br(logs_h($entry['message']))?></div>
                <?php if ($entryContext !== []): ?>
                    <details><summary>Details</summary><pre><?=logs_h(json_encode($entryContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))?></pre></details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
    <?php if ($result['nextCursor'] !== null): ?>
        <div class="logs-more"><a class="btn btn-primary" href="?<?=logs_h(logs_query(['cursor' => $result['nextCursor']]))?>">Load older records</a></div>
    <?php endif; ?>
</main>
<script>
document.querySelectorAll('[data-chip-filter]').forEach((filter) => {
    const list = filter.querySelector('[data-chip-list]');
    const add = filter.querySelector('[data-chip-add]');
    const empty = filter.querySelector('[data-chip-empty]');
    const fieldName = filter.dataset.name;

    const sync = () => {
        const selected = new Set(Array.from(list.querySelectorAll('.logs-chip')).map((chip) => chip.dataset.value));
        empty.hidden = selected.size > 0;
        Array.from(add.options).forEach((option) => {
            if (option.value) option.disabled = selected.has(option.value);
        });
        add.value = '';
    };

    list.addEventListener('click', (event) => {
        const button = event.target.closest('[data-remove-chip]');
        if (!button) return;
        button.closest('.logs-chip').remove();
        sync();
    });

    add.addEventListener('change', () => {
        const option = add.selectedOptions[0];
        if (!option?.value || list.querySelector(`[data-value="${CSS.escape(option.value)}"]`)) return;
        const chip = document.createElement('span');
        const levelClass = fieldName === 'levels[]' ? ` chip-${option.value}` : ' chip-source';
        chip.className = `logs-chip${levelClass}`;
        chip.dataset.value = option.value;
        const label = option.dataset.count === undefined
            ? option.dataset.label
            : `${option.dataset.label} ${option.dataset.count}`;
        chip.innerHTML = `<span></span><button type="button" data-remove-chip aria-label="Remove ${option.dataset.label}">&times;</button>`;
        chip.firstElementChild.textContent = label;
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = fieldName;
        input.value = option.value;
        chip.append(input);
        list.insertBefore(chip, empty);
        sync();
    });

    sync();
});
</script>
</body>
</html>
