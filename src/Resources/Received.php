<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Received (inbound) emails. */
final class Received extends BaseResource
{
    /** @param array<string,mixed> $params @return array<mixed> */
    public function list(array $params = []): array
    {
        return $this->request('GET', '/inbound/emails' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', '/inbound/emails/' . $this->esc($id));
    }

    /** @return array<mixed> */
    public function raw(string $id): array
    {
        return $this->request('GET', '/inbound/emails/' . $this->esc($id) . '/raw');
    }

    /** @return array<mixed> */
    public function attachment(string $id, string $attachmentId): array
    {
        return $this->request('GET', '/inbound/emails/' . $this->esc($id) . '/attachments/' . $this->esc($attachmentId));
    }
}
