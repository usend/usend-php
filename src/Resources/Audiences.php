<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Audiences (the v1 name for segments) and their contacts. */
final class Audiences extends BaseResource
{
    /** @param array<string,mixed> $body @return array<mixed> */
    public function create(array $body): array
    {
        return $this->request('POST', '/audiences', $body);
    }

    /** @return array<mixed> */
    public function list(): array
    {
        return $this->request('GET', '/audiences');
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', '/audiences/' . $this->esc($id));
    }

    /** @return array<mixed> */
    public function remove(string $id): array
    {
        return $this->request('DELETE', '/audiences/' . $this->esc($id));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function createContact(string $audienceId, array $body): array
    {
        return $this->request('POST', '/audiences/' . $this->esc($audienceId) . '/contacts', $body);
    }

    /** @param array<string,mixed> $params @return array<mixed> */
    public function listContacts(string $audienceId, array $params = []): array
    {
        return $this->request('GET', '/audiences/' . $this->esc($audienceId) . '/contacts' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function getContact(string $audienceId, string $idOrEmail): array
    {
        return $this->request('GET', '/audiences/' . $this->esc($audienceId) . '/contacts/' . $this->esc($idOrEmail));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function updateContact(string $audienceId, string $idOrEmail, array $body): array
    {
        return $this->request('PATCH', '/audiences/' . $this->esc($audienceId) . '/contacts/' . $this->esc($idOrEmail), $body);
    }

    /** @return array<mixed> */
    public function removeContact(string $audienceId, string $idOrEmail): array
    {
        return $this->request('DELETE', '/audiences/' . $this->esc($audienceId) . '/contacts/' . $this->esc($idOrEmail));
    }

    /** @return array<mixed> */
    public function importContacts(string $audienceId, string $csv): array
    {
        return $this->request('POST', '/audiences/' . $this->esc($audienceId) . '/contacts/import', ['csv' => $csv]);
    }
}
