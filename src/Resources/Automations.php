<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Automations extends Crud
{
    protected const PATH = '/automations';

    /** @return array<mixed> */
    public function duplicate(string $id): array
    {
        return $this->request('POST', '/automations/' . $this->esc($id) . '/duplicate');
    }

    /** @return array<mixed> */
    public function stop(string $id): array
    {
        return $this->request('POST', '/automations/' . $this->esc($id) . '/stop');
    }

    /** @param array<string,mixed> $params @return array<mixed> */
    public function runs(string $id, array $params = []): array
    {
        return $this->request('GET', '/automations/' . $this->esc($id) . '/runs' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function run(string $id, string $runId): array
    {
        return $this->request('GET', '/automations/' . $this->esc($id) . '/runs/' . $this->esc($runId));
    }
}
