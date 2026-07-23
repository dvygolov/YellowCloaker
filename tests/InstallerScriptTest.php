<?php

use PHPUnit\Framework\TestCase;

class InstallerScriptTest extends TestCase
{
    private string $script;

    protected function setUp(): void
    {
        $path = __DIR__ . '/../install.sh';
        $this->assertFileExists($path);
        $this->script = (string) file_get_contents($path);
    }

    public function testInstallerHasNoIonCubeOrLicensingFlow(): void
    {
        $this->assertStringNotContainsStringIgnoringCase('ioncube', $this->script);
        $this->assertStringNotContainsString('licensing', $this->script);
    }

    public function testInstallerInstallsAndVerifiesMmdbExtension(): void
    {
        $this->assertStringContainsString('libmaxminddb0 libmaxminddb-dev', $this->script);
        $this->assertStringContainsString('php${PHP_VER}-dev php-pear', $this->script);
        $this->assertStringContainsString('pecl install maxminddb', $this->script);
        $this->assertStringContainsString('extension=maxminddb.so', $this->script);
        $this->assertStringContainsString('extension_loaded("maxminddb")', $this->script);
    }

    public function testGeoIpReaderAvoidsPharWhenMaxMindExtensionIsLoaded(): void
    {
        $source = (string) file_get_contents(__DIR__ . '/../bases/ipcountry.php');

        $this->assertStringContainsString("if (!extension_loaded('maxminddb'))", $source);
        $this->assertStringContainsString("require_once __DIR__ . '/geoip2.phar';", $source);
        $this->assertStringContainsString('new \MaxMind\Db\Reader($path)', $source);
        $this->assertStringContainsString("class_exists('\\\\MaxMind\\\\Db\\\\Reader', false)", $source);
    }

    public function testInstallerDownloadsGeoBasesFromSapicsReleases(): void
    {
        $this->assertStringContainsString('https://github.com/sapics/ip-location-db/releases/download/latest/${source_name}', $this->script);
        $this->assertStringContainsString('download_sapics_database "geolite2-country.mmdb" "country.mmdb"', $this->script);
        $this->assertStringContainsString('download_sapics_database "origin-asn.mmdb" "asn.mmdb"', $this->script);
        $this->assertStringNotContainsString('MAXMIND_LICENSE_KEY', $this->script);
    }

    public function testInstallerSupportsBatchAddDomainMode(): void
    {
        $this->assertStringContainsString('--add-domain', $this->script);
        $this->assertStringContainsString('YELLOWTDS_DOMAINS', $this->script);
        $this->assertStringContainsString("IFS=',' read -r -a raw_domains", $this->script);
        $this->assertStringContainsString('PARSED_DOMAINS+=("$domain")', $this->script);
    }

    public function testInstallerSupportsCurlPipeRepositoryDownload(): void
    {
        $this->assertStringContainsString('YELLOWTDS_REPO_ZIP', $this->script);
        $this->assertStringContainsString('https://github.com/dvygolov/YellowTDS/archive/refs/heads/multipleconfigs.zip', $this->script);
        $this->assertStringContainsString('Installer was not run from a YellowTDS checkout; downloading repository ZIP', $this->script);
        $this->assertStringContainsString('Downloaded repository ZIP does not look like YellowTDS', $this->script);
    }

    public function testInstallerInstallsAndEnablesApcuForFpm(): void
    {
        $this->assertStringContainsString('php${PHP_VER}-apcu', $this->script);
        $this->assertStringContainsString('phpenmod -v "${PHP_VER}" -s fpm apcu', $this->script);
        $this->assertStringContainsString('extension_loaded("apcu")', $this->script);
        $this->assertStringContainsString('"$app_dir/caching/runtime"', $this->script);
    }

    public function testInstallerCreatesAndWriteChecksDatabaseAsFpmUser(): void
    {
        $this->assertStringContainsString('initialize_database()', $this->script);
        $this->assertStringContainsString('runuser -u www-data -- "$php_bin" -r', $this->script);
        $this->assertStringContainsString('INSERT INTO common (settings) SELECT settings FROM common LIMIT 1', $this->script);
        $this->assertStringContainsString('PRAGMA quick_check', $this->script);

        $installBlockStart = strpos($this->script, 'run_full_install()');
        $this->assertNotFalse($installBlockStart);
        $installBlock = substr($this->script, $installBlockStart);
        $permissionsPos = strpos($installBlock, 'set_permissions "$app_dir"');
        $databasePos = strpos($installBlock, 'initialize_database "$app_dir"');

        $this->assertNotFalse($permissionsPos);
        $this->assertNotFalse($databasePos);
        $this->assertLessThan($databasePos, $permissionsPos);
    }

    public function testPublishedInstallerLinksUsePrimaryBranch(): void
    {
        $canonicalUrl = 'https://raw.githubusercontent.com/dvygolov/YellowTDS/multipleconfigs/install.sh';

        foreach ([
            __DIR__ . '/../README.md',
            __DIR__ . '/../README.en.md',
            __DIR__ . '/../docs/ru/installation.md',
            __DIR__ . '/../docs/en/installation.md',
        ] as $path) {
            $contents = (string) file_get_contents($path);
            $this->assertStringContainsString($canonicalUrl, $contents, $path);
            $this->assertStringNotContainsString('/YellowTDS/main/install.sh', $contents, $path);
        }
    }

    public function testInstallerHandlesCurlPipeWithoutBashSource(): void
    {
        $guardPos = strpos($this->script, 'if [ -n "${BASH_SOURCE[0]:-}" ]; then');
        $scriptDirPos = strpos($this->script, 'SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"');

        $this->assertNotFalse($guardPos);
        $this->assertNotFalse($scriptDirPos);
        $this->assertLessThan($scriptDirPos, $guardPos);
        $this->assertStringContainsString('SCRIPT_DIR=""', $this->script);
    }

    public function testSkipSslPrintsHttpUrls(): void
    {
        $this->assertStringContainsString('local scheme="https"', $this->script);
        $this->assertStringContainsString('scheme="http"', $this->script);
        $this->assertStringContainsString('Installation complete: ${scheme}://${domain}', $this->script);
        $this->assertStringContainsString('Open ${scheme}://${domain}/${ADMIN_PATH}/', $this->script);
    }

    public function testInstallerStopsWhenHostingPanelIsDetected(): void
    {
        foreach ([
            'FastPanel|/usr/local/fastpanel2',
            'Plesk|/etc/psa/psa.conf',
            'cPanel/WHM|/usr/local/cpanel/cpanel',
            'DirectAdmin|/usr/local/directadmin/directadmin',
            'HestiaCP|/usr/local/hestia/bin/v-list-sys-info',
            'VestaCP|/usr/local/vesta/bin/v-list-sys-info',
            'aaPanel|/www/server/panel/BT-Panel',
            'ISPmanager|/usr/local/mgr5/etc/ispmgr.conf',
            'CyberPanel|/usr/local/CyberCP',
            'CloudPanel|/usr/bin/clpctl',
        ] as $marker) {
            $this->assertStringContainsString($marker, $this->script);
        }

        $this->assertStringContainsString('abort_if_control_panel_installed', $this->script);
        $this->assertStringContainsString('ERROR: ${panel} is installed.', $this->script);
        $this->assertStringContainsString('The automatic YellowTDS VPS installer will not continue', $this->script);
        $this->assertStringContainsString('docs/en/hosting-panels.md', $this->script);
        $this->assertStringContainsString(
            "abort_if_control_panel_installed\n\nif ! command -v apt-get",
            $this->script
        );
    }

    public function testInstallerPreparesStandardNginxSiteDirectories(): void
    {
        $this->assertStringContainsString(
            'install -d -m 0755 /etc/nginx/sites-available /etc/nginx/sites-enabled',
            $this->script
        );
        $this->assertStringContainsString('Failed to prepare nginx site directories', $this->script);
    }

    public function testInstallerGeneratesAndPersistsRandomAdminPath(): void
    {
        $this->assertStringContainsString('YELLOWTDS_ADMIN_PATH', $this->script);
        $this->assertStringContainsString('openssl rand -hex 4', $this->script);
        $this->assertStringContainsString('od -An -N4 -tx1 /dev/urandom', $this->script);
        $this->assertStringContainsString('validate_admin_path', $this->script);
        $this->assertStringContainsString('write_admin_path_setting "$app_dir/settings.php" "$admin_path"', $this->script);
        $this->assertStringContainsString('settings.local.php', $this->script);
    }

    public function testInstallerRenamesPhysicalAdminDirectory(): void
    {
        $this->assertStringContainsString('mv "$app_dir/admin" "$app_dir/$admin_path"', $this->script);
        $this->assertStringContainsString('configure_admin_path "$app_dir"', $this->script);
        $this->assertStringContainsString('configure_domain "$domain" "$app_dir" "$public_ip" "$ADMIN_PATH"', $this->script);
    }

    public function testInstallerChecksDnsBeforeCertbot(): void
    {
        $verifyPos = strpos($this->script, 'verify_domain_points_here "$domain" "$public_ip"');
        $certbotPos = strpos($this->script, 'certbot --nginx -d "$domain"');

        $this->assertNotFalse($verifyPos);
        $this->assertNotFalse($certbotPos);
        $this->assertLessThan($certbotPos, $verifyPos);
    }

    public function testNginxConfigDeniesPrivateFilesAndFolders(): void
    {
        foreach ([
            'location = /settings.php',
            '.(?:db|sqlite|sqlite3|db-wal|db-shm|sql|env|log|cache|bak|old|orig|swp|md)',
            '^/(?:db|logs|ycclogs|tmp)(?:/|$)',
            '^/bases/.*\.(?:mmdb|phar|txt)$',
            'composer\.(?:json|lock)',
            'phpunit\.xml',
        ] as $needle) {
            $this->assertStringContainsString($needle, $this->script);
        }
    }

    public function testNginxConfigDoesNotDenyDynamicOrLegacyAdminPath(): void
    {
        $this->assertStringNotContainsString('location = /admin', $this->script);
        $this->assertStringNotContainsString('location ^~ /admin/', $this->script);
        $this->assertStringNotContainsString('^/caching/(?:devices|currency|proxyvpn|whites_curl)', $this->script);
        $this->assertStringNotContainsString('location = /settings.local.php', $this->script);
    }

    public function testNginxConfigKeepsRuntimeRoutingAndPublicAssetsAvailable(): void
    {
        $this->assertStringContainsString('try_files \$uri \$uri/ /index.php?\$query_string;', $this->script);
        $this->assertStringContainsString('location ~ \.php$', $this->script);
        $this->assertStringNotContainsString('^/(?:caching|admin|js|scripts|thankyou)', $this->script);
        $this->assertStringNotContainsString('^/bases(?:/|$)', $this->script);
    }

    public function testInstallerPrintsGeneratedAdminUrl(): void
    {
        $this->assertStringContainsString('Open ${scheme}://${domain}/${ADMIN_PATH}/', $this->script);
        $this->assertStringNotContainsString('Open ${scheme}://${domain}/admin/', $this->script);
    }

    public function testInstallerSetsCurrencyRefreshCron(): void
    {
        $this->assertStringContainsString('/etc/cron.d/yellowtds-currency', $this->script);
        $this->assertStringContainsString('cron/refresh_currency_rates.php', $this->script);
        $this->assertStringContainsString('setup_currency_cron "$app_dir"', $this->script);
    }
}
