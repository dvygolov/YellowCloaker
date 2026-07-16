<?php

require_once __DIR__ . '/../settings.php';
require_once __DIR__ . '/../plugins/registry.php';
require_once __DIR__ . '/password.php';
require_once __DIR__ . '/accesscontrol.php';

function settingseditor_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/** @return array{currency: array<string, mixed>, vpn: array<string, mixed>} */
function settingseditor_plugin_settings_catalog(array $catalog): array
{
    return [
        'currency' => is_array($catalog['currency'] ?? null) ? $catalog['currency'] : [],
        'vpn' => is_array($catalog['vpn'] ?? null) ? $catalog['vpn'] : [],
    ];
}

function settingseditor_handle_request(): void
{
    if (!check_password(false)) {
        settingseditor_send(['error' => 'Forbidden'], 403);
        return;
    }

    $manager = new SettingsManager(dirname(__DIR__));
    $catalog = PluginRegistry::catalog();
    $settingsCatalog = settingseditor_plugin_settings_catalog($catalog);

    try {
        $manager->recover();
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $reconciled = $manager->reconcilePlugins($settingsCatalog);
            settingseditor_send([
                'settings' => $manager->adminPayload($reconciled['settings']),
                'revision' => $reconciled['revision'],
                'currentDomain' => get_admin_request_domain($_SERVER),
                'currentIp' => get_admin_request_ip($_SERVER),
                'plugins' => $catalog,
                'updates' => [
                    'currentVersion' => trim((string)@file_get_contents(__DIR__ . '/version.txt')),
                    'geoBases' => function_exists('get_bases_version') ? get_bases_version() : trim((string)@file_get_contents(dirname(__DIR__) . '/bases/update.txt')),
                ],
            ]);
            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            settingseditor_send(['error' => 'Method Not Allowed'], 405);
            return;
        }

        $body = json_decode((string)file_get_contents('php://input'), true);
        if (!is_array($body) || !is_array($body['settings'] ?? null) || !isset($body['revision'])) {
            settingseditor_send(['error' => 'Invalid JSON payload'], 422);
            return;
        }

        $saved = $manager->save($body['settings'], (int)$body['revision'], $settingsCatalog);
        settingseditor_send([
            'settings' => $manager->adminPayload($saved['settings']),
            'revision' => $saved['revision'],
            'redirect' => $saved['redirect'],
        ]);
    } catch (SettingsConflictException $e) {
        settingseditor_send(['error' => $e->getMessage(), 'revision' => $manager->revision()], 409);
    } catch (SettingsValidationException $e) {
        settingseditor_send(['error' => $e->getMessage(), 'fields' => $e->errors], 422);
    } catch (Throwable $e) {
        ytds_log('error', 'admin', $e->getMessage(), ['action' => 'settings']);
        settingseditor_send(['error' => $e->getMessage()], 500);
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    settingseditor_handle_request();
}
