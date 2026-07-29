<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/TestDb.php';
if (!defined('YELLOWTDS_EVENTS_API_NO_RUN')) {
    define('YELLOWTDS_EVENTS_API_NO_RUN', true);
}
require_once __DIR__ . '/../../code/api/events.php';

final class RecordingS2sPostbackTransport implements S2sPostbackTransport
{
    /** @var list<array{method:string,url:string}> */
    public array $requests = [];

    public function send(string $method, string $url): bool
    {
        $this->requests[] = ['method' => $method, 'url' => $url];
        return true;
    }

    public function lastError(): string
    {
        return '';
    }
}

final class RecordingWritableSocket implements S2sWritableSocket
{
    public string $bytes = '';
    /** @var list<float> */
    public array $timeouts = [];
    public bool $closed = false;

    public function __construct(private readonly int $maximumWriteBytes = 7)
    {
    }

    public function setWriteTimeout(float $seconds): bool
    {
        $this->timeouts[] = $seconds;
        return $seconds > 0;
    }

    public function write(string $bytes): int|false
    {
        $chunk = substr($bytes, 0, $this->maximumWriteBytes);
        $this->bytes .= $chunk;
        return strlen($chunk);
    }

    public function close(): void
    {
        $this->closed = true;
    }
}

final class RecordingS2sSocketConnector implements S2sSocketConnector
{
    /** @var list<array{endpoint:string,timeout:float,context:array}> */
    public array $connections = [];

    public function __construct(public readonly RecordingWritableSocket $socket)
    {
    }

    public function connect(
        string $endpoint,
        float $timeoutSeconds,
        array $contextOptions
    ): ?S2sWritableSocket {
        $this->connections[] = [
            'endpoint' => $endpoint,
            'timeout' => $timeoutSeconds,
            'context' => $contextOptions,
        ];
        return $this->socket;
    }
}

final class FailingS2sSocketConnector implements S2sSocketConnector
{
    public int $connections = 0;

    public function connect(
        string $endpoint,
        float $timeoutSeconds,
        array $contextOptions
    ): ?S2sWritableSocket {
        $this->connections++;
        return null;
    }
}

final class ThrowingS2sPostbackTransport implements S2sPostbackTransport
{
    public int $requests = 0;

    public function send(string $method, string $url): bool
    {
        $this->requests++;
        throw new RuntimeException('simulated transport failure');
    }

    public function lastError(): string
    {
        return '';
    }
}

final class EventS2sPostbackTest extends TestCase
{
    private TestDb $db;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/yellowtds_event_s2s_' . bin2hex(random_bytes(6)) . '.db';
        $this->db = new TestDb($this->dbPath);
        $this->db->initSchema();
        $this->db->seedCampaign(1, 'Event S2S', [
            'events' => [
                'scroll' => ['use' => false, 'thresholds' => []],
                'time' => ['use' => false, 'thresholds' => []],
                'performance' => ['use' => true],
                'custom' => ['cta_click'],
            ],
            'postback' => [
                's2s' => [[
                    'url' => 'https://hooks.example.test/event'
                        . '?click={clickid}&event={event}&elapsed={event_value}'
                        . '&step={step_index}&variant={variant}&type={trigger_type}',
                    'method' => 'GET',
                    'statuses' => [],
                    'events' => ['cta_click'],
                ]],
            ],
        ]);
        $this->db->seedClicks([[
            'campaign_id' => 1,
            'clickid' => 'click-one',
            'userid' => 'user-one',
            'flow' => 'Flow 1',
            'path' => ['landing a'],
            'step' => 0,
        ]]);
    }

    protected function tearDown(): void
    {
        $this->db->cleanup();
    }

    public function testFirstOrdinaryWriteDispatchesOnceAndDuplicateRetryDoesNot(): void
    {
        $transport = new RecordingS2sPostbackTransport();
        $payload = [
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing a',
            'type' => 'event',
            'event' => 'cta_click',
            'value' => 1234,
        ];

        $first = $this->db->save_step_event(
            $payload['clickid'],
            $payload['step_index'],
            $payload['variant'],
            $payload['event'],
            $payload['value']
        );
        yellowtds_dispatch_first_written_event($this->db, $payload, $first, $transport);

        $retry = $this->db->save_step_event(
            $payload['clickid'],
            $payload['step_index'],
            $payload['variant'],
            $payload['event'],
            50
        );
        yellowtds_dispatch_first_written_event($this->db, $payload, $retry, $transport);

        self::assertSame(Db::STEP_EVENT_CREATED, $first);
        self::assertSame(Db::STEP_EVENT_SAVED, $retry);
        self::assertSame([[
            'method' => 'GET',
            'url' => 'https://hooks.example.test/event'
                . '?click=click-one&event=cta_click&elapsed=1234'
                . '&step=0&variant=landing+a&type=event',
        ]], $transport->requests);
    }

    public function testUnknownAndPerformancePayloadsNeverDispatch(): void
    {
        $transport = new RecordingS2sPostbackTransport();
        $ordinary = [
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing a',
            'type' => 'event',
            'event' => 'unknown_event',
            'value' => 1,
        ];
        $unknownResult = $this->db->save_step_event(
            $ordinary['clickid'],
            $ordinary['step_index'],
            $ordinary['variant'],
            $ordinary['event'],
            $ordinary['value']
        );
        yellowtds_dispatch_first_written_event($this->db, $ordinary, $unknownResult, $transport);

        $performance = [
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing a',
            'type' => 'performance',
            'performance' => ['ttfb' => 100],
        ];
        $performanceResult = $this->db->save_step_performance(
            $performance['clickid'],
            $performance['step_index'],
            $performance['variant'],
            $performance['performance']
        );
        yellowtds_dispatch_first_written_event($this->db, $performance, $performanceResult, $transport);

        self::assertSame(Db::STEP_EVENT_NOT_ALLOWED, $unknownResult);
        self::assertSame(Db::STEP_EVENT_CREATED, $performanceResult);
        self::assertSame([], $transport->requests);
    }

    public function testWriteOnlyTransportCompletesPartialTlsWritesWithoutVisitorHeaders(): void
    {
        $socket = new RecordingWritableSocket(5);
        $connector = new RecordingS2sSocketConnector($socket);
        $transport = new WriteOnlyS2sPostbackTransport($connector, 0.75, 0.25);

        self::assertTrue($transport->send('GET', 'https://example.test:8443/hook?event=cta_click'));
        self::assertSame('tls://example.test:8443', $connector->connections[0]['endpoint']);
        self::assertSame(0.75, $connector->connections[0]['timeout']);
        self::assertTrue($connector->connections[0]['context']['ssl']['verify_peer']);
        self::assertTrue($connector->connections[0]['context']['ssl']['verify_peer_name']);
        self::assertFalse($connector->connections[0]['context']['ssl']['allow_self_signed']);
        self::assertSame('example.test', $connector->connections[0]['context']['ssl']['peer_name']);
        self::assertSame(
            "GET /hook?event=cta_click HTTP/1.1\r\n"
            . "Host: example.test:8443\r\n"
            . "User-Agent: YellowTDS-S2S/1.0\r\n"
            . "Accept: */*\r\n"
            . "Connection: close\r\n\r\n",
            $socket->bytes
        );
        self::assertStringNotContainsString('X-FORWARDED-FOR', $socket->bytes);
        self::assertStringNotContainsString('REMOTE-ADDR', $socket->bytes);
        self::assertGreaterThan(1, count($socket->timeouts));
        self::assertTrue($socket->closed);
    }

    public function testPostMovesQueryToFormBodyAndUnsafeUrlsNeverConnect(): void
    {
        $socket = new RecordingWritableSocket(4096);
        $connector = new RecordingS2sSocketConnector($socket);
        $transport = new WriteOnlyS2sPostbackTransport($connector);

        self::assertTrue($transport->send('POST', 'http://example.test/hook?a=1&b=two'));
        self::assertSame(
            "POST /hook HTTP/1.1\r\n"
            . "Host: example.test\r\n"
            . "User-Agent: YellowTDS-S2S/1.0\r\n"
            . "Accept: */*\r\n"
            . "Connection: close\r\n"
            . "Content-Type: application/x-www-form-urlencoded\r\n"
            . "Content-Length: 9\r\n\r\n"
            . "a=1&b=two",
            $socket->bytes
        );

        foreach ([
            'file:///etc/passwd',
            'https://user:pass@example.test/hook',
            "https://example.test/hook\r\nX-Evil: yes",
            'https://example.test/bad path',
            'https://example.test/%zz',
        ] as $unsafeUrl) {
            self::assertFalse($transport->send('GET', $unsafeUrl), $unsafeUrl);
        }
        self::assertCount(1, $connector->connections);
    }

    public function testConnectionFailureIsNotRetried(): void
    {
        $connector = new FailingS2sSocketConnector();
        $transport = new WriteOnlyS2sPostbackTransport($connector);

        self::assertFalse($transport->send('GET', 'https://example.test/hook'));
        self::assertSame(1, $connector->connections);
        self::assertSame('connection failed', $transport->lastError());
    }

    public function testThrownTransportFailureDoesNotEscapeEventDispatch(): void
    {
        $transport = new ThrowingS2sPostbackTransport();
        $payload = [
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing a',
            'type' => 'event',
            'event' => 'cta_click',
            'value' => 1234,
        ];
        $result = $this->db->save_step_event(
            $payload['clickid'],
            $payload['step_index'],
            $payload['variant'],
            $payload['event'],
            $payload['value']
        );

        yellowtds_dispatch_first_written_event($this->db, $payload, $result, $transport);

        self::assertSame(Db::STEP_EVENT_CREATED, $result);
        self::assertSame(1, $transport->requests);
        self::assertSame(200, yellowtds_events_status_for_result($result));
    }

    public function testSuccessfulResponseIsFinishedBeforeEventSocketDispatch(): void
    {
        $order = [];
        $transport = new class($order) implements S2sPostbackTransport {
            /** @var list<string> */
            private array $order;

            /** @param list<string> $order */
            public function __construct(array &$order)
            {
                $this->order =& $order;
            }

            public function send(string $method, string $url): bool
            {
                $this->order[] = 'dispatch';
                return true;
            }

            public function lastError(): string
            {
                return '';
            }
        };
        $payload = [
            'clickid' => 'click-one',
            'step_index' => 0,
            'variant' => 'landing a',
            'type' => 'event',
            'event' => 'cta_click',
            'value' => 1234,
        ];
        $result = $this->db->save_step_event(
            $payload['clickid'],
            $payload['step_index'],
            $payload['variant'],
            $payload['event'],
            $payload['value']
        );

        ob_start();
        yellowtds_events_finish_response_and_dispatch(
            $this->db,
            $payload,
            $result,
            $transport,
            static function () use (&$order): void {
                $order[] = 'response-finished';
            }
        );
        $response = ob_get_clean();

        self::assertSame('{"ok":true}', $response);
        self::assertSame(['response-finished', 'dispatch'], $order);
    }

    public function testEventPostbackNeverInheritsAConversionStatusFromTheClick(): void
    {
        $url = yellowtds_expand_event_s2s_url(
            'https://hooks.example.test/{status}?status={status}&event={event}',
            [
                'clickid' => 'click-one',
                'userid' => 'user-one',
                'status' => 'stale-click-status',
            ],
            'cta_click',
            1234,
            0,
            'landing a'
        );

        self::assertSame(
            'https://hooks.example.test/?status=&event=cta_click',
            $url
        );
    }
}
