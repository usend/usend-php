# usend for PHP

The official PHP SDK for [usend](https://usend.et), the email API for developers. No dependencies: PHP 8.1 or
newer, `ext-json`, and `ext-curl` (PHP streams are used if curl is missing).

```bash
composer require usend/usend
```

## Quick start

```php
<?php
require 'vendor/autoload.php';

$usend = new Usend\Client(getenv('USEND_API_KEY'));

$email = $usend->emails->send([
    'from' => 'Acme <hello@acme.com>',
    'to' => 'ann@example.com',
    'subject' => 'Your login code',
    'html' => '<p>Your code is <b>482913</b></p>',
]);
echo $email['id'];
```

Methods return the API's JSON as arrays and throw `Usend\UsendException` on failure.

- The key defaults to `USEND_API_KEY`; the base URL to `USEND_BASE_URL` or `https://api.usend.et`.
- Options: `new Usend\Client($key, ['timeout' => 30, 'maxRetries' => 2, 'baseUrl' => '...'])`.
- Resources: `emails`, `events` (custom events), `automations`, `logs`, `received` (inbound mail), `inboxes` (shared mailboxes), `domains`, `apiKeys`, `templates`, `contacts`, `contactProperties`, `contactImports`,
  `segments`, `topics`, `audiences`, `broadcasts`, `webhooks`, `suppressions`, `tools`, `oauth`.

```php
$usend->emails->batch([['from' => '...', 'to' => 'a@x.com', 'subject' => 'Hi', 'html' => '...']]);
$usend->emails->get('email-id');
$usend->emails->list(['limit' => 20]);
$usend->emails->update('email-id', '2030-01-01T09:00:00Z');   // reschedule
$usend->emails->cancel('email-id');
$usend->emails->events('email-id');
$usend->domains->create(['name' => 'acme.com']);
$usend->domains->verify('domain-id');
$usend->templates->publish('welcome');
$usend->contacts->create(['email' => 'ann@example.com', 'first_name' => 'Ann']);
$usend->broadcasts->send('broadcast-id');
```

Pass an idempotency key to make a send safe to repeat:

```php
$usend->emails->send($email, 'order-1042');
```

## Errors and retries

```php
try {
    $usend->emails->send([...]);
} catch (Usend\UsendException $e) {
    $e->getStatusCode(); // 403 (null for network failures)
    $e->getErrorName();  // "validation_error", "rate_limit_exceeded", ...
    $e->getMessage();
}
```

Requests are retried up to `maxRetries` times with backoff on `429` (honoring `Retry-After`), `5xx` and network
errors. A `POST` is only retried after a `5xx` or network error when it carries an idempotency key, so an email is
never sent twice by accident.

## SMTP

Send through SMTP instead; the API key is the password. Port 465 is implicit TLS, 587 is STARTTLS.

```php
$smtp = $usend->smtpConfig();      // smtp.usend.et:465
$smtp = $usend->smtpConfig(587);   // STARTTLS
// or without a client: Usend\Smtp::config(getenv('USEND_API_KEY'), 587)

// ['host' => 'smtp.usend.et', 'port' => 465, 'username' => 'usend', 'password' => 'us_...',
//  'encryption' => 'ssl', 'scheme' => 'smtps', 'dsn' => 'smtps://usend:us_...@smtp.usend.et:465']
```

For a self-hosted platform pass a host, set `USEND_SMTP_HOST`, or construct the client with
`['baseUrl' => 'https://api.example.com']` (gives `smtp.example.com`).

## Webhooks

Verify a delivery ([Standard Webhooks](https://www.standardwebhooks.com)) using the **raw** request body:

```php
try {
    $event = Usend\Webhook::verify(
        file_get_contents('php://input'),
        getallheaders(),                 // or $_SERVER
        getenv('USEND_WEBHOOK_SECRET')   // whsec_...
    );
} catch (Usend\UsendException $e) {
    http_response_code(400);
    exit;
}
```

It returns the decoded event, or throws `UsendException` (name `invalid_webhook`). The default tolerance is 5
minutes (fourth argument).

## Tests

```bash
php tests/run.php
```

Docs: https://usend.et/docs
