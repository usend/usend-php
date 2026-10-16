<?php

declare(strict_types=1);

namespace Usend\Resources;

final class Domains extends Crud
{
    protected const PATH = '/domains';

    /** @return array<mixed> */
    public function verify(string $id): array
    {
        return $this->request('POST', '/domains/' . $this->esc($id) . '/verify');
    }

    /**
     * Publishes the domain's DNS records in the customer's Cloudflare zone. The token is used once.
     *
     * @return array<mixed>
     */
    public function autoConfigure(string $id, string $apiToken, string $provider = 'cloudflare'): array
    {
        return $this->request('POST', '/domains/' . $this->esc($id) . '/auto-configure', ['provider' => $provider, 'api_token' => $apiToken]);
    }

    /** Domain claim. @param array<string,mixed> $body ['name' => '...'] @return array<mixed> */
    public function createClaim(array $body): array
    {
        return $this->request('POST', '/domains/claim', $body);
    }

    /** @return array<mixed> */
    public function claim(string $id): array
    {
        return $this->request('GET', '/domains/claim/' . $this->esc($id));
    }

    /** @return array<mixed> */
    public function verifyClaim(string $id): array
    {
        return $this->request('POST', '/domains/claim/' . $this->esc($id) . '/verify');
    }

    /** @return array<mixed> */
    public function insights(string $id): array
    {
        return $this->request('GET', '/domains/' . $this->esc($id) . '/insights');
    }
}
