<?php

declare(strict_types=1);

namespace Usend\Resources;

final class ApiKeys extends BaseResource
{
    /** @param array<string,mixed> $body name, permission, domain_id? @return array<mixed> */
    public function create(array $body): array
    {
        return $this->request('POST', '/api-keys', $body);
    }

    /** @return array<mixed> */
    public function list(): array
    {
        return $this->request('GET', '/api-keys');
    }

    /** @return array<mixed> */
    public function remove(string $id): array
    {
        return $this->request('DELETE', '/api-keys/' . $this->esc($id));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function update(string $id, array $body): array
    {
        return $this->request('PATCH', '/api-keys/' . $this->esc($id), $body);
    }
}
