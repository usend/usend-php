<?php

declare(strict_types=1);

namespace Usend\Resources;

/** OAuth 2.0 for apps acting on a customer's org (PKCE required). These endpoints take no API key. */
final class OAuth extends BaseResource
{
    public function __construct(\Usend\Http $http, private readonly string $baseUrl)
    {
        parent::__construct($http);
    }

    /** @return array<mixed> */
    public function metadata(): array
    {
        return $this->request('GET', '/.well-known/oauth-authorization-server', null, [], false);
    }

    /** @param array<string,mixed> $body client_name, redirect_uris @return array<mixed> */
    public function register(array $body): array
    {
        return $this->request('POST', '/oauth/register', $body, [], false);
    }

    /**
     * grant_type "authorization_code" (client_id, code, code_verifier, redirect_uri) or "refresh_token" (client_id, refresh_token).
     *
     * @param array<string,mixed> $body
     * @return array<mixed>
     */
    public function token(array $body): array
    {
        return $this->request('POST', '/oauth/token', $body, [], false);
    }

    /** @return array<mixed> */
    public function revoke(string $token, ?string $clientId = null): array
    {
        return $this->request('POST', '/oauth/revoke', array_filter(['token' => $token, 'client_id' => $clientId], static fn ($v) => $v !== null), [], false);
    }

    /** The URL to send the user's browser to. */
    public function authorizeUrl(string $clientId, string $redirectUri, string $scope, string $codeChallenge, ?string $state = null): string
    {
        $q = array_filter([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
        ], static fn ($v) => $v !== null);

        return $this->baseUrl . '/oauth/authorize?' . http_build_query($q, '', '&', PHP_QUERY_RFC3986);
    }
}
