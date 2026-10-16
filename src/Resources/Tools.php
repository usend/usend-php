<?php

declare(strict_types=1);

namespace Usend\Resources;

/** Deliverability tools. */
final class Tools extends BaseResource
{
    /** @return array<mixed> */
    public function dmarcCheck(string $domain): array
    {
        return $this->request('POST', '/tools/dmarc-check', ['domain' => $domain]);
    }
}
