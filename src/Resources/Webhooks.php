<?php

declare(strict_types=1);

namespace Usend\Resources;

use Usend\Webhook;

final class Webhooks extends Crud
{
    protected const PATH = '/webhooks';

    /** @param array<string,mixed> $params @return array<mixed> */
    public function deliveries(string $id, array $params = []): array
    {
        return $this->request('GET', '/webhooks/' . $this->esc($id) . '/deliveries' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function replay(string $id, string $deliveryId): array
    {
        return $this->request('POST', '/webhooks/' . $this->esc($id) . '/deliveries/' . $this->esc($deliveryId) . '/replay');
    }

    /**
     * Verifies a delivery. See Usend\Webhook::verify.
     *
     * @param array<string,mixed> $headers
     * @return array<string,mixed>
     */
    public function verify(string $payload, array $headers, string $secret, int $toleranceSeconds = 300): array
    {
        return Webhook::verify($payload, $headers, $secret, $toleranceSeconds);
    }

    /** Returns the new signing secret; the old one stops signing at once. @return array<mixed> */
    public function rotateSecret(string $id): array
    {
        return $this->request('POST', '/webhooks/' . $this->esc($id) . '/rotate-secret');
    }

    /** Webhook events across endpoints. @param array<string,mixed> $params @return array<mixed> */
    public function events(array $params = []): array
    {
        return $this->request('GET', '/webhooks/events' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function event(string $eventId): array
    {
        return $this->request('GET', '/webhooks/events/' . $this->esc($eventId));
    }

    /** @return array<mixed> */
    public function replayEvent(string $eventId): array
    {
        return $this->request('POST', '/webhooks/events/' . $this->esc($eventId) . '/replay');
    }

    /** @return array<mixed> */
    public function eventAttempts(string $eventId): array
    {
        return $this->request('GET', '/webhooks/events/' . $this->esc($eventId) . '/attempts');
    }
}
