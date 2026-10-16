<?php

declare(strict_types=1);

namespace Usend;

use Usend\Resources\ApiKeys;
use Usend\Resources\Audiences;
use Usend\Resources\Automations;
use Usend\Resources\Broadcasts;
use Usend\Resources\ContactImports;
use Usend\Resources\ContactProperties;
use Usend\Resources\Contacts;
use Usend\Resources\Domains;
use Usend\Resources\Emails;
use Usend\Resources\Events;
use Usend\Resources\Inboxes;
use Usend\Resources\Logs;
use Usend\Resources\OAuth;
use Usend\Resources\Received;
use Usend\Resources\Segments;
use Usend\Resources\Suppressions;
use Usend\Resources\Templates;
use Usend\Resources\Tools;
use Usend\Resources\ShortLinks;
use Usend\Resources\Topics;
use Usend\Resources\Webhooks;

/**
 * Client for the usend API.
 *
 *     $usend = new Usend\Client(getenv('USEND_API_KEY'));
 *     $usend->emails->send(['from' => 'Acme <hi@acme.com>', 'to' => 'ann@example.com', 'subject' => 'Hi', 'html' => '<p>Hi</p>']);
 */
final class Client
{
    public const VERSION = '1.3.0';
    public const DEFAULT_BASE_URL = 'https://api.usend.et';

    public readonly string $baseUrl;
    public readonly Emails $emails;
    public readonly Events $events;
    public readonly Logs $logs;
    public readonly Received $received;
    public readonly Inboxes $inboxes;
    public readonly Automations $automations;
    public readonly ContactProperties $contactProperties;
    public readonly ContactImports $contactImports;
    public readonly Tools $tools;
    public readonly OAuth $oauth;
    public readonly Domains $domains;
    public readonly ApiKeys $apiKeys;
    public readonly Templates $templates;
    public readonly Contacts $contacts;
    public readonly Segments $segments;
    public readonly Topics $topics;
    public readonly ShortLinks $shortLinks;
    public readonly Audiences $audiences;
    public readonly Broadcasts $broadcasts;
    public readonly Webhooks $webhooks;
    public readonly Suppressions $suppressions;

    private readonly string $apiKey;

    /**
     * @param string|null $apiKey defaults to the USEND_API_KEY environment variable
     * @param array{baseUrl?: string, timeout?: float|int, maxRetries?: int, retryDelay?: float|int, sleeper?: callable, useCurl?: bool} $options
     *        baseUrl defaults to USEND_BASE_URL or https://api.usend.et; timeout is in seconds (30);
     *        maxRetries (2) applies to 429, 5xx and network errors, with retryDelay (0.5s) doubling each time.
     *        A POST is only retried after a 5xx or network error when it carries an idempotency key.
     */
    public function __construct(?string $apiKey = null, array $options = [])
    {
        $key = $apiKey ?? (getenv('USEND_API_KEY') ?: null);
        if ($key === null || $key === '') {
            throw new UsendException('Missing API key. Pass it to new Client("us_...") or set USEND_API_KEY.', 'missing_api_key');
        }
        $this->apiKey = $key;
        $this->baseUrl = rtrim($options['baseUrl'] ?? (getenv('USEND_BASE_URL') ?: self::DEFAULT_BASE_URL), '/');

        $http = new Http(
            $key,
            $this->baseUrl,
            (float) ($options['timeout'] ?? 30),
            (int) ($options['maxRetries'] ?? 2),
            (float) ($options['retryDelay'] ?? 0.5),
            $options['sleeper'] ?? null,
            $options['useCurl'] ?? null,
        );
        $this->emails = new Emails($http);
        $this->events = new Events($http);
        $this->logs = new Logs($http);
        $this->received = new Received($http);
        $this->inboxes = new Inboxes($http);
        $this->automations = new Automations($http);
        $this->contactProperties = new ContactProperties($http);
        $this->contactImports = new ContactImports($http);
        $this->tools = new Tools($http);
        $this->oauth = new OAuth($http, $this->baseUrl);
        $this->domains = new Domains($http);
        $this->apiKeys = new ApiKeys($http);
        $this->templates = new Templates($http);
        $this->contacts = new Contacts($http);
        $this->segments = new Segments($http);
        $this->topics = new Topics($http);
        $this->shortLinks = new ShortLinks($http);
        $this->audiences = new Audiences($http);
        $this->broadcasts = new Broadcasts($http);
        $this->webhooks = new Webhooks($http);
        $this->suppressions = new Suppressions($http);
    }

    /**
     * SMTP settings for this key. Port 465 is implicit TLS, 587 is STARTTLS. See Smtp::config().
     *
     * @return array{host: string, port: int, username: string, password: string, encryption: string, scheme: string, dsn: string}
     */
    public function smtpConfig(int $port = 465, ?string $host = null): array
    {
        return Smtp::config($this->apiKey, $port, $host, $this->baseUrl);
    }
}
