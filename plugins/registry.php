<?php

require_once __DIR__ . '/base.php';

final class PluginRegistry
{
    /** @var array<string, array<string, object>> */
    private static array $plugins = [];
    /** @var array<int, array{type: string, file: string, error: string}> */
    private static array $errors = [];
    private static bool $discovered = false;

    /** @return array<string, BaseCurrencyPlugin> */
    public static function currencyPlugins(): array
    {
        self::discover();
        return self::$plugins['currency'];
    }

    /** @return array<string, BaseProxyVpnPlugin> */
    public static function vpnPlugins(): array
    {
        self::discover();
        return self::$plugins['vpn'];
    }

    /**
     * @return array{
     *   currency: array<string, array{id: string, class: string, file: string}>,
     *   vpn: array<string, array{id: string, class: string, file: string}>,
     *   errors: array<int, array{type: string, file: string, error: string}>
     * }
     */
    public static function catalog(): array
    {
        self::discover();
        $catalog = ['currency' => [], 'vpn' => [], 'errors' => self::$errors];
        foreach (['currency', 'vpn'] as $type) {
            foreach (self::$plugins[$type] as $id => $plugin) {
                $reflection = new ReflectionClass($plugin);
                $catalog[$type][$id] = [
                    'id' => $id,
                    'class' => $reflection->getName(),
                    'file' => basename((string)$reflection->getFileName()),
                ];
            }
        }
        return $catalog;
    }

    public static function reset(): void
    {
        self::$plugins = [];
        self::$errors = [];
        self::$discovered = false;
    }

    private static function discover(): void
    {
        if (self::$discovered) {
            return;
        }
        self::$discovered = true;
        self::$plugins = ['currency' => [], 'vpn' => []];
        self::$errors = [];

        self::discoverType('currency', __DIR__ . '/currency', BaseCurrencyPlugin::class);
        self::discoverType('vpn', __DIR__ . '/vpn', BaseProxyVpnPlugin::class);
    }

    private static function discoverType(string $type, string $directory, string $baseClass): void
    {
        $files = glob($directory . '/*Plugin.php') ?: [];
        sort($files, SORT_STRING);
        $candidates = [];

        foreach ($files as $file) {
            $expectedClass = basename($file, '.php');
            try {
                require_once $file;
                if (!class_exists($expectedClass, false)) {
                    throw new RuntimeException("Expected class $expectedClass was not declared");
                }
                $reflection = new ReflectionClass($expectedClass);
                if (!$reflection->isInstantiable() || !$reflection->isSubclassOf($baseClass)) {
                    throw new RuntimeException("Class $expectedClass must extend $baseClass");
                }
                $constructor = $reflection->getConstructor();
                if ($constructor !== null && $constructor->getNumberOfRequiredParameters() > 0) {
                    throw new RuntimeException("Class $expectedClass must not require constructor arguments");
                }
                $plugin = $reflection->newInstance();
                $id = trim((string)$plugin->getName());
                if (preg_match('/^[A-Za-z0-9._-]{1,64}$/', $id) !== 1) {
                    throw new RuntimeException("Invalid plugin ID: $id");
                }
                $candidates[$id][] = ['plugin' => $plugin, 'file' => $file];
            } catch (Throwable $e) {
                self::addError($type, $file, $e->getMessage());
            }
        }

        foreach ($candidates as $id => $matches) {
            if (count($matches) !== 1) {
                foreach ($matches as $match) {
                    self::addError($type, $match['file'], "Duplicate plugin ID: $id");
                }
                continue;
            }
            self::$plugins[$type][$id] = $matches[0]['plugin'];
        }
    }

    private static function addError(string $type, string $file, string $error): void
    {
        self::$errors[] = ['type' => $type, 'file' => basename($file), 'error' => $error];
        if (function_exists('ytds_log')) {
            ytds_log('error', 'plugins', $error, ['type' => $type, 'file' => basename($file)]);
        }
    }
}
