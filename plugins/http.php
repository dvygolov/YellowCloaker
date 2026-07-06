<?php

class PluginHttpRequest
{
    public function __construct(
        public string $plugin,
        public string $url,
        public int $timeout = 5,
        public int $connectTimeout = 5,
        public array $headers = []
    ) {
    }
}

class PluginHttpResponse
{
    public function __construct(
        public string $plugin,
        public string|false $content,
        public int $httpCode,
        public string $error = '',
        public int $errno = 0
    ) {
    }

    public function isOk(): bool
    {
        return $this->errno === 0 && $this->error === '' && $this->httpCode >= 200 && $this->httpCode < 300 && $this->content !== false;
    }
}

class PluginHttpClient
{
    /**
     * @param PluginHttpRequest[] $requests
     * @return array<string, PluginHttpResponse>
     */
    public static function runParallel(array $requests): array
    {
        if (empty($requests)) {
            return [];
        }

        $multi = curl_multi_init();
        $handles = [];

        foreach ($requests as $request) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $request->url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT => $request->timeout,
                CURLOPT_CONNECTTIMEOUT => $request->connectTimeout,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
            ]);
            if (!empty($request->headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $request->headers);
            }
            curl_multi_add_handle($multi, $ch);
            $handles[spl_object_id($ch)] = [$ch, $request];
        }

        do {
            $status = curl_multi_exec($multi, $running);
            if ($running) {
                curl_multi_select($multi, 1.0);
            }
        } while ($running && $status === CURLM_OK);

        $responses = [];
        foreach ($handles as [$ch, $request]) {
            $responses[$request->plugin] = new PluginHttpResponse(
                $request->plugin,
                curl_multi_getcontent($ch),
                (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
                curl_error($ch),
                curl_errno($ch)
            );
            curl_multi_remove_handle($multi, $ch);
            curl_close($ch);
        }

        curl_multi_close($multi);
        return $responses;
    }
}
