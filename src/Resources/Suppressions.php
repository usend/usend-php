<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Suppressions extends BaseResource
{
    /** @param array<string,mixed> $params @return array<mixed> */
    public function list(array $params = []): array
    {
        return $this->request('GET', '/suppressions' . $this->qs($params));
    }

    /** @param array<string,mixed> $body ['email' => '...'] @return array<mixed> */
    public function create(array $body): array
    {
        return $this->request('POST', '/suppressions', $body);
    }

    /** @return array<mixed> */
    public function remove(string $id): array
    {
        return $this->request('DELETE', '/suppressions/' . $this->esc($id));
    }

    /** @param string $idOrEmail @return array<mixed> */
    public function get(string $idOrEmail): array
    {
        return $this->request('GET', '/suppressions/' . $this->esc($idOrEmail));
    }

    /** @param list<string> $emails up to 100 addresses @return array<mixed> */
    public function batchCreate(array $emails): array
    {
        return $this->request('POST', '/suppressions/batch', ['emails' => $emails]);
    }

    /** @param list<string> $emails @return array<mixed> */
    public function batchRemove(array $emails): array
    {
        return $this->request('DELETE', '/suppressions/batch', ['emails' => $emails]);
    }
}
