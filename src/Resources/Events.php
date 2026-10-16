<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Custom events that trigger automations (not email delivery events: see Emails::events). */
final class Events extends Crud
{
    protected const PATH = '/events';

    /**
     * Fires an event for a contact: ['event' => ..., 'contact_id' => ... | 'email' => ..., 'payload' => [...]]. The API answers 202.
     *
     * @param array<string,mixed> $body
     * @return array<mixed>
     */
    public function send(array $body): array
    {
        return $this->request('POST', '/events/send', $body);
    }
}
