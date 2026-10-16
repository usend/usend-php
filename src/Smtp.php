<?php

declare(strict_types=1);

namespace Usend;

/** SMTP settings for sending through usend. The API key is the password. */
final class Smtp
{
    public const DEFAULT_HOST = 'smtp.usend.et';

    /**
     * Port 465 is implicit TLS (encryption "ssl"), 587 is STARTTLS (encryption "tls").
     * The host is smtp.usend.et; for a self-hosted platform pass $host, set USEND_SMTP_HOST, or pass
     * $baseUrl ("https://api.x.com" gives "smtp.x.com").
     *
     * @return array{host: string, port: int, username: string, password: string, encryption: string, scheme: string, dsn: string}
     */
    public static function config(?string $apiKey = null, int $port = 465, ?string $host = null, ?string $baseUrl = null): array
    {
        $key = $apiKey ?? (getenv('USEND_API_KEY') ?: null);
        if ($key === null || $key === '') {
            throw new UsendException('Missing API key. Pass it to Smtp::config() or set USEND_API_KEY.', 'missing_api_key');
        }
        if ($port !== 465 && $port !== 587) {
            throw new UsendException('usend accepts SMTP on port 465 (TLS) or 587 (STARTTLS).', 'invalid_port');
        }
        $host = $host ?: (getenv('USEND_SMTP_HOST') ?: self::hostFor($baseUrl));

        return [
            'host' => $host,
            'port' => $port,
            'username' => 'usend',
            'password' => $key,
            'encryption' => $port === 465 ? 'ssl' : 'tls',
            // Laravel 11+ ("scheme") and Symfony Mailer ("dsn"): smtps is implicit TLS, smtp upgrades with STARTTLS.
            'scheme' => $port === 465 ? 'smtps' : 'smtp',
            'dsn' => sprintf('%s://usend:%s@%s:%d', $port === 465 ? 'smtps' : 'smtp', rawurlencode($key), $host, $port),
        ];
    }

    public static function hostFor(?string $baseUrl): string
    {
        if ($baseUrl === null || $baseUrl === '') {
            return self::DEFAULT_HOST;
        }
        $hostname = parse_url($baseUrl, PHP_URL_HOST);
        if (is_string($hostname) && str_starts_with($hostname, 'api.')) {
            return 'smtp.' . substr($hostname, 4);
        }

        return self::DEFAULT_HOST;
    }
}
