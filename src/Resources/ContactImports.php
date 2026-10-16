<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Bulk contact imports (POST /contacts/imports) and their progress. */
final class ContactImports extends BaseResource
{
    /** @param array<string,mixed> $body @return array<mixed> */
    public function create(array $body): array
    {
        return $this->request('POST', '/contacts/imports', $body);
    }

    /** @param array<string,mixed> $params @return array<mixed> */
    public function list(array $params = []): array
    {
        return $this->request('GET', '/contacts/imports' . $this->qs($params));
    }

    /** @return array<mixed> */
    public function get(string $id): array
    {
        return $this->request('GET', '/contacts/imports/' . $this->esc($id));
    }
}
