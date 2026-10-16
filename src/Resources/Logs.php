<?php

declare(strict_types=1);

namespace Usend\Resources;

/** API request logs. */
final class Logs extends BaseResource
{
    /** @param array<string,mixed> $params status, method, days, limit, after, ... @return array<mixed> */
    public function list(array $params = []): array
    {
        return $this->request('GET', '/logs' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', '/logs/' . $this->esc($id));
    }

    /** @return array<mixed> */
    public function userAgents(): array
    {
        return $this->request('GET', '/logs/user-agents');
    }
}
