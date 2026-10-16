<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Segments extends BaseResource
{
    /** @param array<string,mixed> $body ['name' => '...'] @return array<mixed> */
    public function create(array $body): array
    {
        return $this->request('POST', '/segments', $body);
    }

    /** @return array<mixed> */
    public function list(): array
    {
        return $this->request('GET', '/segments');
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', '/segments/' . $this->esc($id));
    }

    /** @return array<mixed> */
    public function remove(string $id): array
    {
        return $this->request('DELETE', '/segments/' . $this->esc($id));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function update(string $id, array $body): array
    {
        return $this->request('PATCH', '/segments/' . $this->esc($id), $body);
    }

    /** @param array<string,mixed> $params limit, after, before, subscription ("subscribed"|"unsubscribed") @return array<mixed> */
    public function contacts(string $id, array $params = []): array
    {
        return $this->request('GET', '/segments/' . $this->esc($id) . '/contacts' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function metrics(string $id): array
    {
        return $this->request('GET', '/segments/' . $this->esc($id) . '/metrics');
    }
}
