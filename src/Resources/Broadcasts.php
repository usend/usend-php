<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Broadcasts extends Crud
{
    protected const PATH = '/broadcasts';

    /** Sends now, or at $scheduledAt. @return array<mixed> */
    public function send(string $id, ?string $scheduledAt = null): array
    {
        return $this->request('POST', '/broadcasts/' . $this->esc($id) . '/send', $scheduledAt !== null ? ['scheduled_at' => $scheduledAt] : new \stdClass());
    }

    /** Sends a test copy to one address. @return array<mixed> */
    public function test(string $id, string $to): array
    {
        return $this->request('POST', '/broadcasts/' . $this->esc($id) . '/test', ['to' => $to]);
    }

    /** @return array<mixed> */
    public function cancel(string $id): array
    {
        return $this->request('POST', '/broadcasts/' . $this->esc($id) . '/cancel');
    }

    /** @return array<mixed> */
    public function duplicate(string $id): array
    {
        return $this->request('POST', '/broadcasts/' . $this->esc($id) . '/duplicate');
    }

    /**
     * Recipients who had an event. $type is required: sent, delivered, opened, clicked, bounced, complained,
     * unsubscribed or suppressed. Rows are ['id', 'contact_id', 'email', 'count'?, 'bounce_type'?, 'clicked_links'?].
     *
     * @param array<string,mixed> $params limit, after, before, email, bounce_type
     * @return array<mixed>
     */
    public function recipients(string $id, string $type, array $params = []): array
    {
        $params['type'] = $type;

        return $this->request('GET', '/broadcasts/' . $this->esc($id) . '/recipients' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function clickedLinks(string $id): array
    {
        return $this->request('GET', '/broadcasts/' . $this->esc($id) . '/clicked-links');
    }

    /** @return array<mixed> */
    public function performance(string $id): array
    {
        return $this->request('GET', '/broadcasts/' . $this->esc($id) . '/performance');
    }
}
