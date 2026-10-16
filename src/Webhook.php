<?php

declare(strict_types=1);

namespace Usend;

/** Webhook signature verification (Standard Webhooks). */
final class Webhook
{
    /**
     * Verifies a delivery and returns the decoded event; throws UsendException (name "invalid_webhook") if invalid.
     *
     * @param string               $payload          the raw request body exactly as received (file_get_contents('php://input'))
     * @param array<string,mixed>  $headers          request headers, any key case (getallheaders(), or $_SERVER with HTTP_ keys)
     * @param string               $secret           the endpoint's signing secret (whsec_...)
     * @param int                  $toleranceSeconds
     * @return array<string,mixed>
     */
    public static function verify(string $payload, array $headers, string $secret, int $toleranceSeconds = 300): array
    {
        $h = [];
        foreach ($headers as $k => $v) {
            $name = strtolower((string) $k);
            if (str_starts_with($name, 'http_')) {
                $name = substr($name, 5);
            }
            $h[str_replace('_', '-', $name)] = is_array($v) ? (string) ($v[0] ?? '') : (string) $v;
        }
        $id = $h['webhook-id'] ?? '';
        $ts = $h['webhook-timestamp'] ?? '';
        $sigs = $h['webhook-signature'] ?? '';
        if ($id === '' || $ts === '' || $sigs === '') {
            throw self::invalid('Missing webhook-id, webhook-timestamp or webhook-signature header');
        }
        if (!ctype_digit($ts)) {
            throw self::invalid('Invalid webhook timestamp');
        }
        if (abs(time() - (int) $ts) > $toleranceSeconds) {
            throw self::invalid('Webhook timestamp is too old or too new');
        }
        $key = base64_decode(preg_replace('/^whsec_/', '', $secret) ?? '', true);
        if ($key === false) {
            throw self::invalid('Invalid webhook secret');
        }
        $expected = base64_encode(hash_hmac('sha256', $id . '.' . $ts . '.' . $payload, $key, true));
        $ok = false;
        foreach (explode(' ', $sigs) as $part) {
            $pieces = explode(',', $part, 2);
            if (count($pieces) === 2 && $pieces[0] === 'v1' && hash_equals($expected, $pieces[1])) {
                $ok = true;
            }
        }
        if (!$ok) {
            throw self::invalid('Invalid webhook signature');
        }
        try {
            $event = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw self::invalid('Webhook body is not valid JSON');
        }

        return is_array($event) ? $event : [];
    }

    private static function invalid(string $message): UsendException
    {
        return new UsendException($message, 'invalid_webhook');
    }
}
