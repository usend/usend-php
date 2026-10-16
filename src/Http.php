<?php

declare(strict_types=1);

namespace Usend;

/**
 * Minimal HTTP layer: JSON in and out, retries on 429 and 5xx with backoff. Uses ext-curl when
 * loaded, PHP streams otherwise.
 *
 * @internal
 */
final class Http
{
    private const RETRY_STATUSES = [429, 500, 502, 503, 504];

    /** @var callable(float): void */
    private $sleeper;

    /**
     * @param callable(float): void|null $sleeper called with seconds to wait before a retry (tests override it)
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly float $timeout,
        private readonly int $maxRetries,
        private readonly float $retryDelay,
        ?callable $sleeper = null,
        private readonly ?bool $useCurl = null,
    ) {
        $this->sleeper = $sleeper ?? static function (float $s): void {
            if ($s > 0) {
                usleep((int) ($s * 1_000_000));
            }
        };
    }

    /**
     * @param array<mixed>|\stdClass|null $body
     * @param array<string,string>        $headers
     * @return array<mixed> the decoded JSON body ([] when empty)
     */
    public function request(string $method, string $path, array|\stdClass|null $body = null, array $headers = [], bool $auth = true): array
    {
        $payload = null;
        $h = [
            'User-Agent' => 'usend-php/' . Client::VERSION,
            'Accept' => 'application/json',
        ];
        if ($body !== null) {
            try {
                $payload = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } catch (\JsonException $e) {
                throw new UsendException('Could not encode the request body as JSON: ' . $e->getMessage(), 'invalid_body');
            }
            $h['Content-Type'] = 'application/json';
        }
        if ($auth) {
            $h['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        $h = array_merge($h, $headers);
        $url = $this->baseUrl . $path;
        // A POST without an idempotency key may already have been processed: never repeat it after a 5xx or network error.
        $repeatable = strtoupper($method) !== 'POST' || isset($headers['Idempotency-Key']);

        $attempt = 0;
        while (true) {
            $attempt++;
            [$status, $raw, $respHeaders, $netError] = $this->send($method, $url, $payload, $h);

            if ($netError !== null) {
                if ($repeatable && $attempt <= $this->maxRetries) {
                    ($this->sleeper)($this->backoff($attempt, null));
                    continue;
                }
                throw new UsendException($netError);
            }
            if ($status >= 200 && $status < 300) {
                return $this->decode($raw);
            }
            if (in_array($status, self::RETRY_STATUSES, true) && $attempt <= $this->maxRetries && ($status === 429 || $repeatable)) {
                ($this->sleeper)($this->backoff($attempt, $respHeaders['retry-after'] ?? null));
                continue;
            }
            $err = json_decode($raw, true);
            if (!is_array($err)) {
                $err = [];
            }
            throw new UsendException(
                is_string($err['message'] ?? null) ? $err['message'] : 'HTTP ' . $status,
                is_string($err['name'] ?? null) ? $err['name'] : 'application_error',
                $status,
            );
        }
    }

    /** @return array<mixed> */
    private function decode(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new UsendException('The API returned a response that is not JSON.', 'invalid_response');
        }

        return $data;
    }

    private function backoff(int $attempt, ?string $retryAfter): float
    {
        if ($retryAfter !== null && is_numeric($retryAfter)) {
            return min((float) $retryAfter, 30.0);
        }

        return min($this->retryDelay * (2 ** ($attempt - 1)), 8.0);
    }

    /**
     * @param array<string,string> $headers
     * @return array{0:int,1:string,2:array<string,string>,3:?string} status, body, lowercase response headers, network error
     */
    private function send(string $method, string $url, ?string $body, array $headers): array
    {
        $curl = $this->useCurl ?? function_exists('curl_init');

        return $curl ? $this->sendCurl($method, $url, $body, $headers) : $this->sendStream($method, $url, $body, $headers);
    }

    /** @param array<string,string> $headers */
    private function sendCurl(string $method, string $url, ?string $body, array $headers): array
    {
        $lines = ['Expect:'];
        foreach ($headers as $k => $v) {
            $lines[] = $k . ': ' . $v;
        }
        $respHeaders = [];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $lines,
            CURLOPT_TIMEOUT_MS => (int) ($this->timeout * 1000),
            CURLOPT_CONNECTTIMEOUT_MS => (int) (min($this->timeout, 10.0) * 1000),
            CURLOPT_HEADERFUNCTION => static function ($ch, string $line) use (&$respHeaders): int {
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return strlen($line);
            },
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            return [0, '', [], curl_error($ch) ?: 'Network error'];
        }

        return [(int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE), (string) $raw, $respHeaders, null];
    }

    /** @param array<string,string> $headers */
    private function sendStream(string $method, string $url, ?string $body, array $headers): array
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            $lines[] = $k . ': ' . $v;
        }
        $http = [
            'method' => strtoupper($method),
            'header' => implode("\r\n", $lines),
            'timeout' => $this->timeout,
            'ignore_errors' => true,
            'follow_location' => 0,
        ];
        if ($body !== null) {
            $http['content'] = $body;
        }
        $http_response_header = null;
        $raw = @file_get_contents($url, false, stream_context_create(['http' => $http]));
        $responseLines = $http_response_header ?? [];
        if ($raw === false && $responseLines === []) {
            $last = error_get_last();

            return [0, '', [], $last['message'] ?? 'Network error'];
        }
        $status = 0;
        $respHeaders = [];
        foreach ($responseLines as $line) {
            if (str_starts_with($line, 'HTTP/')) {
                $status = (int) (explode(' ', $line, 3)[1] ?? 0);
                $respHeaders = [];
                continue;
            }
            $parts = explode(':', $line, 2);
            if (count($parts) === 2) {
                $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
        }

        return [$status, (string) $raw, $respHeaders, null];
    }
}
