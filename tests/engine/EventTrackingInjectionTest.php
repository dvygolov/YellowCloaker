<?php

use PHPUnit\Framework\TestCase;

final class EventTrackingInjectionTest extends TestCase
{
    private array $server;

    protected function setUp(): void
    {
        $this->server = $_SERVER;
        $_SERVER['HTTP_HOST'] = 'tracker.example';
        $_SERVER['SERVER_NAME'] = 'tracker.example';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['SERVER_PORT'] = 443;
        $_SERVER['SCRIPT_NAME'] = '/tds/index.php';
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
    }

    public function testDisabledEventsDoNotInjectTransportOrModules(): void
    {
        $html = '<html><body>Landing</body></html>';
        $events = EventSettings::fromArray([]);

        $this->assertSame(
            $html,
            add_event_tracking($html, $events, 'click-1', 0, 'landing-a')
        );
    }

    public function testEnabledCollectorsWithoutValidThresholdsInjectNothing(): void
    {
        $html = '<html><body>Landing</body></html>';
        $events = EventSettings::fromArray([
            'scroll' => ['use' => true, 'thresholds' => ''],
            'time' => ['use' => true, 'thresholds' => [0, 86401]],
        ]);

        $this->assertSame(
            $html,
            add_event_tracking($html, $events, 'click-empty', 0, 'landing-a')
        );
    }

    public function testOnlyEnabledOrdinaryModulesAreInjected(): void
    {
        $events = EventSettings::fromArray([
            'scroll' => ['use' => true, 'thresholds' => [25, 75]],
            'time' => ['use' => false, 'thresholds' => [30]],
            'performance' => ['use' => false],
            'custom' => [],
        ]);

        $html = add_event_tracking(
            '<html><body>Landing</body></html>',
            $events,
            'click-ordinary',
            2,
            'landing-b'
        );

        $this->assertStringContainsString('__yellowTdsEventTransport', $html);
        $this->assertStringContainsString('requestAnimationFrame(reportDepth)', $html);
        $this->assertStringContainsString('[25,75]', $html);
        $this->assertStringNotContainsString('visibleMilliseconds', $html);
        $this->assertStringNotContainsString('global.ytdsEvent', $html);
        $this->assertStringNotContainsString('var webVitals=', $html);
        $this->assertStringContainsString('"click-ordinary"', $html);
        $this->assertStringContainsString('const stepIndex = 2;', $html);
        $this->assertStringContainsString('"landing-b"', $html);
    }

    public function testCustomApiIsInjectedOnlyForConfiguredAllowlist(): void
    {
        $events = EventSettings::fromArray([
            'custom' => ['cta_click', 'form_open'],
        ]);

        $html = add_event_tracking(
            '<html><body></body></html>',
            $events,
            'click-custom',
            0,
            'landing-a'
        );

        $this->assertStringContainsString('global.ytdsEvent = function', $html);
        $this->assertStringContainsString('["cta_click","form_open"]', $html);
        $this->assertStringContainsString('return transport.sendEvent(normalizedName)', $html);
        $this->assertStringNotContainsString('requestAnimationFrame(reportDepth)', $html);
        $this->assertStringNotContainsString('visibleMilliseconds', $html);
        $this->assertStringNotContainsString('var webVitals=', $html);
    }

    public function testPerformanceInjectsPinnedBundleAndAbsoluteCollector(): void
    {
        $events = EventSettings::fromArray([
            'performance' => ['use' => true],
        ]);

        $html = add_event_tracking(
            '<html><body></body></html>',
            $events,
            'click-performance',
            1,
            'landing-performance'
        );

        $this->assertStringContainsString('https:\\/\\/tracker.example\\/tds\\/api\\/events.php', $html);
        $this->assertStringContainsString('var webVitals=function', $html);
        $this->assertStringContainsString(
            "const requiredMetrics = ['ttfb', 'fcp', 'lcp', 'inp', 'cls'];",
            $html
        );
        $this->assertStringContainsString('reportAllChanges: true', $html);
        $this->assertStringContainsString('10000', $html);
        $this->assertStringContainsString('"click-performance"', $html);
        $this->assertStringContainsString('"landing-performance"', $html);
    }

    public function testPhpConnectUsesAbsoluteCredentiallessCorsTransport(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/tds/api/phpconnect.php';
        $events = EventSettings::fromArray([
            'custom' => ['cta_click'],
        ]);

        $html = add_event_tracking(
            '<html><body></body></html>',
            $events,
            'click-php-connect',
            0,
            'landing-php-connect'
        );

        $this->assertStringContainsString('https:\\/\\/tracker.example\\/tds\\/api\\/events.php', $html);
        $this->assertStringContainsString("'Content-Type': 'text/plain;charset=UTF-8'", $html);
        $this->assertStringContainsString("mode: 'cors'", $html);
        $this->assertStringContainsString("credentials: 'omit'", $html);
    }

    public function testJsConnectSuppressesPerformanceButKeepsOrdinaryEvents(): void
    {
        $_SERVER['SCRIPT_NAME'] = '/tds/js/index.php';
        $source = '<html><body></body></html>';
        $performanceOnly = EventSettings::fromArray([
            'performance' => ['use' => true],
        ]);
        $this->assertSame(
            $source,
            add_event_tracking(
                $source,
                $performanceOnly,
                'click-js-performance-only',
                0,
                'landing-js-performance-only'
            )
        );

        $events = EventSettings::fromArray([
            'performance' => ['use' => true],
            'custom' => ['cta_click'],
        ]);

        $html = add_event_tracking(
            $source,
            $events,
            'click-js-connect',
            0,
            'landing-js-connect'
        );

        $this->assertStringContainsString('__yellowTdsEventTransport', $html);
        $this->assertStringContainsString('global.ytdsEvent = function', $html);
        $this->assertStringNotContainsString('var webVitals=', $html);
        $this->assertStringNotContainsString('requiredMetrics', $html);
    }

    public function testEventScriptsHandleUppercaseAndMissingBodyTags(): void
    {
        $events = EventSettings::fromArray(['custom' => ['cta_click']]);

        $uppercase = add_event_tracking(
            '<HTML><BODY>Landing</BODY></HTML>',
            $events,
            'click-uppercase',
            0,
            'landing-uppercase'
        );
        $this->assertStringContainsString('__yellowTdsEventTransport', $uppercase);
        $this->assertLessThan(
            stripos($uppercase, '</body>'),
            strpos($uppercase, '__yellowTdsEventTransport')
        );

        $fragment = add_event_tracking(
            '<main>Landing fragment</main>',
            $events,
            'click-fragment',
            0,
            'landing-fragment'
        );
        $this->assertStringStartsWith('<main>Landing fragment</main><script>', $fragment);
        $this->assertStringContainsString('global.ytdsEvent = function', $fragment);
    }

    public function testLoadStepInjectsCollectorsOnlyIntoLandingRootIndex(): void
    {
        $runtimeName = 'runtime-event-injection-' . bin2hex(random_bytes(6));
        $runtimePath = __DIR__ . DIRECTORY_SEPARATOR . $runtimeName;
        $landingPath = $runtimePath . DIRECTORY_SEPARATOR . 'landings'
            . DIRECTORY_SEPARATOR . 'landing-events';
        $nestedPath = $landingPath . DIRECTORY_SEPARATOR . 'internal';
        self::assertTrue(mkdir($nestedPath, 0755, true));
        file_put_contents(
            $landingPath . DIRECTORY_SEPARATOR . 'index.html',
            '<html><head></head><body>Root landing</body></html>'
        );
        file_put_contents(
            $nestedPath . DIRECTORY_SEPARATOR . 'page.html',
            '<html><head></head><body>Nested HTML</body></html>'
        );
        file_put_contents(
            $nestedPath . DIRECTORY_SEPARATOR . 'page.php',
            '<?php echo "<html><head></head><body>Nested PHP</body></html>";'
        );

        $originalCachingDir = $GLOBALS['cloSettings']['cachingDir'];
        $hadOriginalDb = array_key_exists('db', $GLOBALS);
        $originalDb = $GLOBALS['db'] ?? null;
        $GLOBALS['cloSettings']['cachingDir'] = '../tests/engine/' . $runtimeName;
        $GLOBALS['db'] = new class {
            public function get_click_by_clickid(string $clickid): array
            {
                return ['userid' => 'event-test-user'];
            }

            public function get_click_step_mvt(string $clickid, int $step): array
            {
                return [];
            }

            public function update_click_step_mvt(string $clickid, int $step, array $mvt): bool
            {
                return true;
            }
        };

        try {
            $settings = json_decode(
                (string)file_get_contents(__DIR__ . '/../../code/db/default.json'),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
            $settings['events'] = [
                'scroll' => ['use' => true, 'thresholds' => [50]],
                'time' => ['use' => true, 'thresholds' => [30]],
                'performance' => ['use' => true],
                'custom' => ['cta_click'],
            ];
            $campaign = new Campaign(1, $settings);
            $flow = FlowSettings::fromArray([
                'name' => 'Events flow',
                'steps' => [[
                    'action' => 'folder',
                    'folders' => [[
                        'name' => 'landing-events',
                        'loadtype' => 'base',
                        'weight' => 100,
                        'mvt' => ['enabled' => false, 'tests' => []],
                    ]],
                    'redirect' => ['urls' => [], 'type' => 302],
                ]],
            ]);

            $root = load_step(
                $campaign,
                $flow,
                0,
                'landing-events',
                'click-root'
            );
            $nestedHtml = load_step(
                $campaign,
                $flow,
                0,
                'landing-events',
                'click-nested-html',
                false,
                'internal/page.html'
            );
            $nestedPhp = load_step(
                $campaign,
                $flow,
                0,
                'landing-events',
                'click-nested-php',
                false,
                'internal/page.php'
            );

            self::assertStringContainsString('__yellowTdsEventTransport', $root);
            self::assertStringContainsString('global.ytdsEvent = function', $root);
            self::assertStringContainsString('requestAnimationFrame(reportDepth)', $root);
            self::assertStringContainsString('visibleMilliseconds', $root);
            self::assertStringContainsString('var webVitals=function', $root);
            self::assertStringNotContainsString('__yellowTdsEventTransport', $nestedHtml);
            self::assertStringNotContainsString('__yellowTdsEventTransport', $nestedPhp);
        } finally {
            $GLOBALS['cloSettings']['cachingDir'] = $originalCachingDir;
            if ($hadOriginalDb) {
                $GLOBALS['db'] = $originalDb;
            } else {
                unset($GLOBALS['db']);
            }
            $this->removeDirectory($runtimePath);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }
        rmdir($path);
    }
}
