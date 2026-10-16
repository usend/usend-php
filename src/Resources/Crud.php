<?php

declare(strict_types=1);

namespace Usend\Resources;

/** create / list / get / update / remove on one path. */
abstract class Crud extends BaseResource
{
    protected const PATH = '';

    /** @param array<string,mixed> $body @return array<mixed> */
    public function create(array $body): array
    {
        return $this->request('POST', static::PATH, $body);
    }

    /** @param array<string,mixed> $params @return array<mixed> */
    public function list(array $params = []): array
    {
        return $this->request('GET', static::PATH . $this->qs($params));
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', static::PATH . '/' . $this->esc($id));
    }

    /** @param array<string,mixed> $body @return array<mixed> */
    public function update(string $id, array $body): array
    {
        return $this->request('PATCH', static::PATH . '/' . $this->esc($id), $body);
    }

    /** @return array<mixed> */
    public function remove(string $id): array
    {
        return $this->request('DELETE', static::PATH . '/' . $this->esc($id));
    }
}
