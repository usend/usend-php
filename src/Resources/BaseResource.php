<?php

declare(strict_types=1);

namespace Usend\Resources;

use Usend\Http;

/**
 * Base for resource objects. Every method returns the API's decoded JSON (an array) and throws
 * Usend\UsendException on failure.
 */
abstract class BaseResource
{
    public function __construct(protected readonly Http $http)
    {
    }

    /**
     * @param array<mixed>|\stdClass|null $body
     * @param array<string,string>        $headers
     * @return array<mixed>
     */
    protected function request(string $method, string $path, array|\stdClass|null $body = null, array $headers = [], bool $auth = true): array
    {
        return $this->http->request($method, $path, $body, $headers, $auth);
    }

    protected function esc(string|int $value): string
    {
        return rawurlencode((string) $value);
    }

    /** @param array<string,mixed>|null $params null values are dropped, booleans become true/false */
    protected function qs(?array $params): string
    {
        $pairs = [];
        foreach ($params ?? [] as $k => $v) {
            if ($v === null) {
                continue;
            }
            $pairs[$k] = is_bool($v) ? ($v ? 'true' : 'false') : $v;
        }

        return $pairs === [] ? '' : '?' . http_build_query($pairs, '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array<string,string> */
    protected function idem(?string $key): array
    {
        return $key !== null && $key !== '' ? ['Idempotency-Key' => $key] : [];
    }
}
