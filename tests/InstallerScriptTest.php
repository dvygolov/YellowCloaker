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
        $this->assertStringContainsString('https://github.com/dvygolov/YellowTDS/archive/refs/heads/main.zip', $this->script);
        $this->assertStringContainsString('Installer was not run from a YellowTDS checkout; downloading repository ZIP', $this->script);
        $this->assertStringContainsString('Downloaded repository ZIP does not look like YellowTDS', $this->script);
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
            '^/caching/(?:devices|currency|proxyvpn|whites_curl)(?:/|$)',
            '^/bases/.*\.(?:mmdb|phar|txt)$',
            'composer\.(?:json|lock)',
            'phpunit\.xml',
        ] as $needle) {
            $this->assertStringContainsString($needle, $this->script);
        }
    }

    public function testNginxConfigKeepsRuntimeRoutingAndPublicAssetsAvailable(): void
    {
        $this->assertStringContainsString('try_files \$uri \$uri/ /index.php?\$query_string;', $this->script);
        $this->assertStringContainsString('location ~ \.php$', $this->script);
        $this->assertStringNotContainsString('^/(?:caching|admin|js|scripts|thankyou)', $this->script);
        $this->assertStringNotContainsString('^/bases(?:/|$)', $this->script);
    }

    public function testInstallerSetsCurrencyRefreshCron(): void
    {
        $this->assertStringContainsString('/etc/cron.d/yellowtds-currency', $this->script);
        $this->assertStringContainsString('cron/refresh_currency_rates.php', $this->script);
        $this->assertStringContainsString('setup_currency_cron "$app_dir"', $this->script);
    }
}
