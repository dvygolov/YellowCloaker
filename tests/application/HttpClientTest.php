<?php

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../code/requestfunc.php';

class HttpClientTest extends TestCase
{
    private static $process;
    private static array $pipes = [];
    private static string $baseUrl;

    public static function setUpBeforeClass(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
        if ($socket === false) {
            self::markTestSkipped("Cannot allocate local port: $error");
        }
        $address = stream_socket_get_name($socket, false);
        fclose($socket);
        $port = (int)substr(strrchr($address, ':'), 1);
        self::$baseUrl = "http://127.0.0.1:$port";

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        self::$process = proc_open(
            [PHP_BINARY, '-d', 'display_errors=0', '-S', "127.0.0.1:$port", __DIR__ . '/http_router.php'],
            $descriptors,
            self::$pipes,
            __DIR__
        );
        if (!is_resource(self::$process)) {
            self::markTestSkipped('Cannot start local PHP server');
        }
        fclose(self::$pipes[0]);

        $ready = false;
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $connection = @fsockopen('127.0.0.1', $port, $errno, $error, 0.1);
            if (is_resource($connection)) {
                fclose($connection);
                $ready = true;
                break;
            }
            usleep(20000);
        }
        if (!$ready) {
            self::tearDownAfterClass();
            self::markTestSkipped('Local PHP server did not start');
        }
    }

    public static function tearDownAfterClass(): void
    {
        foreach (self::$pipes as $pipe) {
            if (is_resource($pipe)) fclose($pipe);
        }
        if (is_resource(self::$process)) proc_terminate(self::$process);
    }

    public function testSingleRequestSupportsRawPostAndHeaders(): void
    {
        $response = HttpClient::send(new HttpRequest(
            id: 'post',
            url: self::$baseUrl . '/echo',
            method: 'POST',
            body: '{"hello":"world"}',
            headers: ['Content-Type: application/json', 'X-Test: shared-client'],
        ));
        $payload = json_decode((string)$response->content, true);
        $this->assertTrue($response->isOk());
        $this->assertSame('POST', $payload['method']);
        $this->assertSame('{"hello":"world"}', $payload['body']);
        $this->assertSame('shared-client', $payload['testHeader']);
    }

    public function testParallelRequestsAreKeyedById(): void
    {
        $responses = HttpClient::sendParallel([
            new HttpRequest('first', self::$baseUrl . '/echo'),
            new HttpRequest('second', self::$baseUrl . '/error'),
        ]);
        $this->assertSame(['first', 'second'], array_keys($responses));
        $this->assertTrue($responses['first']->isOk());
        $this->assertSame(503, $responses['second']->httpCode());
        $this->assertFalse($responses['second']->isOk());
    }

    public function testRedirectAndResponseHeadersAreCaptured(): void
    {
        $response = HttpClient::send(new HttpRequest(
            id: 'headers',
            url: self::$baseUrl . '/redirect',
            followRedirects: true,
            captureHeaders: true,
        ));
        $this->assertTrue($response->isOk());
        $this->assertSame(['captured'], $response->headers['x-ywb-test']);
        $this->assertSame(['one=1', 'two=2'], $response->headers['set-cookie']);
    }
}
