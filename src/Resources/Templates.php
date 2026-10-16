<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Templates extends Crud
{
    protected const PATH = '/templates';

    /** Publishes the draft; sends use the last published version. @return array<mixed> */
    public function publish(string $idOrAlias): array
    {
        return $this->request('POST', '/templates/' . $this->esc($idOrAlias) . '/publish');
    }

    /** @return array<mixed> */
    public function duplicate(string $idOrAlias): array
    {
        return $this->request('POST', '/templates/' . $this->esc($idOrAlias) . '/duplicate');
    }

    /** @return array<mixed> */
    public function versions(string $idOrAlias): array
    {
        return $this->request('GET', '/templates/' . $this->esc($idOrAlias) . '/versions');
    }

    /** @return array<mixed> */
    public function version(string $idOrAlias, string $versionId): array
    {
        return $this->request('GET', '/templates/' . $this->esc($idOrAlias) . '/versions/' . $this->esc($versionId));
    }

    /** @return array<mixed> */
    public function restoreVersion(string $idOrAlias, string $versionId): array
    {
        return $this->request('POST', '/templates/' . $this->esc($idOrAlias) . '/versions/' . $this->esc($versionId) . '/restore');
    }
}
