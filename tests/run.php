<?php

// Dependency-free tests: `php tests/run.php`. Starts a mock server with `php -S` on a free port.

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Usend\\')) {
        $file = __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, 6)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

use Usend\Client;
use Usend\Smtp;
use Usend\UsendException;
use Usend\Webhook;

$failures = 0;
$checks = 0;

function check(string $name, bool $ok, string $detail = ''): void
{
    global $failures, $checks;
    $checks++;
    if (!$ok) {
        $failures++;
        echo "FAIL $name $detail\n";
    }
}

function eq(string $name, mixed $expected, mixed $actual): void
{
    check($name, $expected === $actual, 'expected ' . var_export($expected, true) . ' got ' . var_export($actual, true));
}

function throws(string $name, callable $fn, ?int $status = null, ?string $errName = null): ?UsendException
{
    try {
        $fn();
    } catch (UsendException $e) {
        if ($status !== null) {
            eq("$name status", $status, $e->getStatusCode());
        }
        if ($errName !== null) {
            eq("$name name", $errName, $e->getErrorName());
        }
        check("$name threw", true);

        return $e;
    }
    check("$name threw", false, 'no exception');

    return null;
}

// ---- mock server ----
$port = 0;
$sock = stream_socket_server('tcp://127.0.0.1:0');
$port = (int) substr(strrchr((string) stream_socket_get_name($sock, false), ':'), 1);
fclose($sock);
$stateFile = sys_get_temp_dir() . '/usend_mock_' . getmypid() . '.json';
@unlink($stateFile);
$proc = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:$port", __DIR__ . '/mock_router.php'],
    [0 => ['pipe', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
    $pipes,
    null,
    ['USEND_MOCK_STATE' => $stateFile] + getenv(),
);
register_shutdown_function(static function () use ($proc, $stateFile): void {
    proc_terminate($proc);
    @unlink($stateFile);
});
for ($i = 0; $i < 50; $i++) {
    $c = @fsockopen('127.0.0.1', $port, $e, $s, 0.2);
    if ($c) {
        fclose($c);
        break;
    }
    usleep(100_000);
}

$sleeps = [];
$base = "http://127.0.0.1:$port";

foreach ([true, false] as $useCurl) {
    $label = $useCurl ? 'curl' : 'streams';
    if ($useCurl && !function_exists('curl_init')) {
        echo "skipping curl transport (ext-curl not loaded)\n";
        continue;
    }
    @unlink($stateFile);
    $client = new Client('us_test', ['baseUrl' => $base, 'useCurl' => $useCurl, 'sleeper' => function (float $s) use (&$sleeps): void {
        $sleeps[] = $s;
    }]);

    // request shape
    $r = $client->emails->send(['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 'Hi', 'html' => '<p>x/y</p>'], 'idem-1');
    eq("$label send method", 'POST', $r['method']);
    eq("$label send uri", '/emails', $r['uri']);
    eq("$label auth", 'Bearer us_test', $r['headers']['authorization']);
    eq("$label idempotency", 'idem-1', $r['headers']['idempotency-key']);
    eq("$label content-type", 'application/json', $r['headers']['content-type']);
    check("$label user-agent", str_starts_with($r['headers']['user-agent'], 'usend-php/'));
    eq("$label body", ['from' => 'a@b.com', 'to' => 'c@d.com', 'subject' => 'Hi', 'html' => '<p>x/y</p>'], json_decode($r['body'], true));

    $r = $client->emails->batch([['from' => 'a@b.com']], null, 'permissive');
    eq("$label batch uri", '/emails/batch', $r['uri']);
    eq("$label batch header", 'permissive', $r['headers']['x-batch-validation']);
    check("$label batch has no idempotency header", !isset($r['headers']['idempotency-key']));

    // query + escaping
    $r = $client->emails->list(['limit' => 5, 'after' => null, 'status' => 'a b', 'flag' => true]);
    eq("$label list uri", '/emails?limit=5&status=a%20b&flag=true', $r['uri']);
    $r = $client->contacts->get('a b@x.com/y');
    eq("$label escaping", '/contacts/a%20b%40x.com%2Fy', $r['uri']);

    // other resources
    $r = $client->emails->cancel('e 1');
    eq("$label cancel", 'POST /emails/e%201/cancel', $r['method'] . ' ' . $r['uri']);
    $r = $client->emails->update('e1', '2030-01-01T00:00:00Z');
    eq("$label update", ['PATCH', ['scheduled_at' => '2030-01-01T00:00:00Z']], [$r['method'], json_decode($r['body'], true)]);
    $r = $client->domains->autoConfigure('d1', 'tok');
    eq("$label autoconfigure", ['provider' => 'cloudflare', 'api_token' => 'tok'], json_decode($r['body'], true));
    $r = $client->broadcasts->send('b1');
    eq("$label broadcast send empty object", '{}', $r['body']);
    $r = $client->broadcasts->send('b1', '2030-01-01T00:00:00Z');
    eq("$label broadcast send scheduled", ['scheduled_at' => '2030-01-01T00:00:00Z'], json_decode($r['body'], true));
    $r = $client->webhooks->replay('w1', 'd1');
    eq("$label replay", '/webhooks/w1/deliveries/d1/replay', $r['uri']);
    $r = $client->received->list();
    eq("$label inboxes", '/inbound/emails', $r['uri']);
    $r = $client->topics->remove('t1');
    eq("$label topics remove", 'DELETE /topics/t1', $r['method'] . ' ' . $r['uri']);
    $r = $client->audiences->createContact('a1', ['email' => 'x@y.com']);
    eq("$label audience contact", '/audiences/a1/contacts', $r['uri']);
    $r = $client->emails->events("e1");
    eq("$label events", '/emails/e1/events', $r['uri']);
    $r = $client->templates->publish('welcome');
    eq("$label template publish", '/templates/welcome/publish', $r['uri']);
    $r = $client->apiKeys->list();
    eq("$label api keys", '/api-keys', $r['uri']);
    $r = $client->segments->create(['name' => 'VIP']);
    eq("$label segments", '/segments', $r['uri']);
    $r = $client->suppressions->list(['limit' => 1]);
    eq("$label suppressions", '/suppressions?limit=1', $r['uri']);
    $r = $client->contacts->addToSegment('a@b.com', 's1');
    eq("$label contact segment", '/contacts/a%40b.com/segments/s1', $r['uri']);
    $r = $client->logs->list();
    eq("$label logs", '/logs', $r['uri']);

    // newer resources
    $r = $client->broadcasts->recipients('b1', 'bounced', ['bounce_type' => 'permanent', 'limit' => 10]);
    eq("$label recipients", '/broadcasts/b1/recipients?bounce_type=permanent&limit=10&type=bounced', $r['uri']);
    $cases = [
        ['POST /events/send', fn () => $client->events->send(['event' => 'x', 'email' => 'a@b.com'])],
        ['GET /events', fn () => $client->events->list()],
        ['POST /automations/a1/stop', fn () => $client->automations->stop('a1')],
        ['GET /automations/a1/runs/r1', fn () => $client->automations->run('a1', 'r1')],
        ['POST /inboxes/i/threads/t/emails/e/reply', fn () => $client->inboxes->reply('i', 't', 'e', ['text' => 'hi'])],
        ['POST /inboxes/i/drafts/d/send', fn () => $client->inboxes->sendDraft('i', 'd')],
        ['POST /domains/claim/c1/verify', fn () => $client->domains->verifyClaim('c1')],
        ['GET /domains/d1/insights', fn () => $client->domains->insights('d1')],
        ['GET /emails/e1/insights', fn () => $client->emails->insights('e1')],
        ['POST /emails/e1/share', fn () => $client->emails->share('e1')],
        ['GET /emails/metrics', fn () => $client->emails->metrics()],
        ['POST /templates/t/versions/v/restore', fn () => $client->templates->restoreVersion('t', 'v')],
        ['GET /contacts/imports/i1', fn () => $client->contactImports->get('i1')],
        ['GET /segments/s1/metrics', fn () => $client->segments->metrics('s1')],
        ['POST /webhooks/w1/rotate-secret', fn () => $client->webhooks->rotateSecret('w1')],
        ['POST /webhooks/events/x1/replay', fn () => $client->webhooks->replayEvent('x1')],
        ['DELETE /suppressions/batch', fn () => $client->suppressions->batchRemove(['a@b.com'])],
        ['PATCH /api-keys/k1', fn () => $client->apiKeys->update('k1', ['name' => 'n'])],
        ['POST /broadcasts/b1/cancel', fn () => $client->broadcasts->cancel('b1')],
        ['POST /tools/dmarc-check', fn () => $client->tools->dmarcCheck('a.com')],
    ];
    foreach ($cases as [$want, $call]) {
        $r = $call();
        eq("$label $want", $want, $r['method'] . ' ' . $r['uri']);
    }
    $r = $client->oauth->token(['grant_type' => 'refresh_token', 'client_id' => 'c', 'refresh_token' => 'r']);
    eq("$label oauth no api key", '/oauth/token', $r['uri']);
    check("$label oauth has no Authorization", !isset($r['headers']['authorization']));
    check("$label authorize url", str_contains($client->oauth->authorizeUrl('c', 'http://localhost/cb', 'sending_access', 'x'), $base . '/oauth/authorize?response_type=code&client_id=c'));

    // errors and retries
    $e = throws("$label api error", fn () => $client->domains->get('error'), 422, 'validation_error');
    eq("$label api error message", 'bad input', $e?->getMessage());

    $sleeps = [];
    $r = $client->domains->get('ratelimited');
    eq("$label 429 retried", 2, $r['hits']);
    eq("$label 429 honored Retry-After", [0.0], $sleeps);

    $sleeps = [];
    $r = $client->domains->get('flaky');
    eq("$label 503 retried on GET", 2, $r['hits']);
    eq("$label 503 backoff", [0.5], $sleeps);

    $sleeps = [];
    throws("$label gives up", fn () => $client->domains->get('boom'), 500, 'application_error');
    eq("$label retries before giving up", [0.5, 1.0], $sleeps);

    // a POST without an idempotency key is not repeated after a 5xx
    $sleeps = [];
    throws("$label POST not retried", fn () => $client->emails->cancel('boom'), 500, 'application_error');
    eq("$label POST without key not retried", [], $sleeps);

    // ...but is, with an idempotency key (the router fails the first send with that key)
    @unlink($stateFile);
    $sleeps = [];
    $r = $client->emails->send(['from' => 'a@b.com'], 'flaky-key');
    eq("$label POST with key retried", [2, [0.5]], [$r['hits'], $sleeps]);
}

// network error
$dead = new Client('k', ['baseUrl' => 'http://127.0.0.1:1', 'maxRetries' => 0, 'timeout' => 2]);
$e = throws('network error', fn () => $dead->domains->list(), null, 'application_error');
eq('network error status', null, $e?->getStatusCode());

// client
$old = getenv('USEND_API_KEY');
putenv('USEND_API_KEY');
throws('missing key', fn () => new Client(), null, 'missing_api_key');
if ($old !== false) {
    putenv("USEND_API_KEY=$old");
}
eq('default base url', 'https://api.usend.et', (new Client('k'))->baseUrl);
eq('base url trailing slash', 'https://api.x.com', (new Client('k', ['baseUrl' => 'https://api.x.com/']))->baseUrl);

// smtp
$s = (new Client('us_k'))->smtpConfig();
eq('smtp defaults', ['smtp.usend.et', 465, 'usend', 'us_k', 'ssl', 'smtps'], [$s['host'], $s['port'], $s['username'], $s['password'], $s['encryption'], $s['scheme']]);
eq('smtp dsn', 'smtps://usend:us_k@smtp.usend.et:465', $s['dsn']);
$s = (new Client('k', ['baseUrl' => 'https://api.example.com/']))->smtpConfig(587);
eq('smtp derived host', ['smtp.example.com', 587, 'tls', 'smtp'], [$s['host'], $s['port'], $s['encryption'], $s['scheme']]);
eq('smtp host override', 'relay.x.com', Smtp::config('k', 465, 'relay.x.com')['host']);
throws('smtp bad port', fn () => Smtp::config('k', 25), null, 'invalid_port');
eq('smtp dsn encodes key', 'smtps://usend:a%2Fb@smtp.usend.et:465', Smtp::config('a/b')['dsn']);

// webhooks
$secret = 'whsec_' . base64_encode('super-secret-key');
$sign = static fn (string $id, string $ts, string $payload): string => base64_encode(hash_hmac('sha256', "$id.$ts.$payload", 'super-secret-key', true));
$payload = '{"type":"email.delivered"}';
$ts = (string) time();
$headers = ['Webhook-Id' => 'msg_1', 'Webhook-Timestamp' => $ts, 'Webhook-Signature' => 'v1,bogus v1,' . $sign('msg_1', $ts, $payload)];
eq('webhook ok', 'email.delivered', Webhook::verify($payload, $headers, $secret)['type']);
$server = ['HTTP_WEBHOOK_ID' => 'msg_1', 'HTTP_WEBHOOK_TIMESTAMP' => $ts, 'HTTP_WEBHOOK_SIGNATURE' => 'v1,' . $sign('msg_1', $ts, $payload)];
eq('webhook $_SERVER keys', 'email.delivered', Webhook::verify($payload, $server, $secret)['type']);
$bad = ['webhook-id' => 'm', 'webhook-timestamp' => $ts, 'webhook-signature' => 'v1,' . $sign('m', $ts, 'other')];
throws('webhook bad signature', fn () => Webhook::verify($payload, $bad, $secret), null, 'invalid_webhook');
$old = (string) (time() - 3600);
$stale = ['webhook-id' => 'm', 'webhook-timestamp' => $old, 'webhook-signature' => 'v1,' . $sign('m', $old, $payload)];
throws('webhook stale', fn () => Webhook::verify($payload, $stale, $secret), null, 'invalid_webhook');
throws('webhook missing headers', fn () => Webhook::verify($payload, [], $secret), null, 'invalid_webhook');

echo $failures === 0 ? "OK ($checks checks)\n" : "$failures of $checks checks FAILED\n";
exit($failures === 0 ? 0 : 1);
