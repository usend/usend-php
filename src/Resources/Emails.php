<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Emails extends BaseResource
{
    /**
     * Sends one email. Pass an idempotency key to make the call safe to repeat.
     *
     * @param array<string,mixed> $email from, to, subject, html/text or template, ...
     * @return array<mixed> e.g. ['id' => '...']
     */
    public function send(array $email, ?string $idempotencyKey = null): array
    {
        return $this->request('POST', '/emails', $email, $this->idem($idempotencyKey));
    }

    /**
     * Sends up to 100 emails in one call.
     *
     * @param list<array<string,mixed>> $emails
     * @param string|null $validation "strict" (default) or "permissive"
     * @return array<mixed>
     */
    public function batch(array $emails, ?string $idempotencyKey = null, ?string $validation = null): array
    {
        $headers = $this->idem($idempotencyKey);
        if ($validation !== null) {
            $headers['x-batch-validation'] = $validation;
        }

        return $this->request('POST', '/emails/batch', $emails, $headers);
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', '/emails/' . $this->esc($id));
    }

    /** @param array<string,mixed> $params limit, after, before, ... @return array<mixed> */
    public function list(array $params = []): array
    {
        return $this->request('GET', '/emails' . $this->qs($params));
    }

    /** Reschedules a scheduled email. @return array<mixed> */
    public function update(string $id, string $scheduledAt): array
    {
        return $this->request('PATCH', '/emails/' . $this->esc($id), ['scheduled_at' => $scheduledAt]);
    }

    /** Cancels a scheduled email. @return array<mixed> */
    public function cancel(string $id): array
    {
        return $this->request('POST', '/emails/' . $this->esc($id) . '/cancel');
    }

    /** @return array<mixed> */
    public function events(string $id): array
    {
        return $this->request('GET', '/emails/' . $this->esc($id) . '/events');
    }

    /** @return array<mixed> */
    public function attachments(string $id): array
    {
        return $this->request('GET', '/emails/' . $this->esc($id) . '/attachments');
    }

    /** @return array<mixed> */
    public function attachment(string $id, string $attachmentId): array
    {
        return $this->request('GET', '/emails/' . $this->esc($id) . '/attachments/' . $this->esc($attachmentId));
    }

    /** Aggregate sending metrics (start_date, end_date, metrics, dimensions, granularity, ...). @param array<string,mixed> $params @return array<mixed> */
    public function metrics(array $params = []): array
    {
        return $this->request('GET', '/emails/metrics' . $this->qs($params));
    }

    /** Creates a public read-only link; $expiresIn like "10m", "2 hours", "1 day" (max 48h). @return array<mixed> */
    public function share(string $id, ?string $expiresIn = null): array
    {
        return $this->request('POST', '/emails/' . $this->esc($id) . '/share', $expiresIn !== null ? ['expires_in' => $expiresIn] : new \stdClass());
    }

    /** Sends the content to the signed-in user's own address (dashboard sessions and OAuth tokens). @param array<string,mixed> $body @return array<mixed> */
    public function test(array $body): array
    {
        return $this->request('POST', '/emails/test', $body);
    }

    /** Deliverability findings for the stored message. @return array<mixed> */
    public function insights(string $id): array
    {
        return $this->request('GET', '/emails/' . $this->esc($id) . '/insights');
    }
}
