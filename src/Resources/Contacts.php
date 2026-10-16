<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Account-wide contacts. get/update/remove accept an id or an email address. */
final class Contacts extends Crud
{
    protected const PATH = '/contacts';

    /** @return array<mixed> */
    public function import(string $csv): array
    {
        return $this->request('POST', '/contacts/import', ['csv' => $csv]);
    }

    /** @return array<mixed> */
    public function stats(): array
    {
        return $this->request('GET', '/contacts/stats');
    }

    /** @return array<mixed> */
    public function segments(string $idOrEmail): array
    {
        return $this->request('GET', '/contacts/' . $this->esc($idOrEmail) . '/segments');
    }

    /** @return array<mixed> */
    public function addToSegment(string $idOrEmail, string $segmentId): array
    {
        return $this->request('POST', '/contacts/' . $this->esc($idOrEmail) . '/segments/' . $this->esc($segmentId));
    }

    /** @return array<mixed> */
    public function removeFromSegment(string $idOrEmail, string $segmentId): array
    {
        return $this->request('DELETE', '/contacts/' . $this->esc($idOrEmail) . '/segments/' . $this->esc($segmentId));
    }

    /** @return array<mixed> */
    public function topics(string $idOrEmail): array
    {
        return $this->request('GET', '/contacts/' . $this->esc($idOrEmail) . '/topics');
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function updateTopics(string $idOrEmail, array $body): array
    {
        return $this->request('PATCH', '/contacts/' . $this->esc($idOrEmail) . '/topics', $body);
    }
}
