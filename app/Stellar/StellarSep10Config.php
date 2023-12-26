<?php

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\ISep10Config;

class StellarSep10Config implements ISep10Config
{

    public function getWebAuthDomain(): ?string
    {
        return config('stellar.sep10.web_auth_domain');
    }

    public function getHomeDomains(): array
    {
        return config('stellar.sep10.home_domains');
    }

    public function getAuthTimeout(): int
    {
        return config('stellar.sep10.auth_timeout', 300);
    }

    public function getSep10SigningSeed(): string
    {
        return config('stellar.sep10.server_signing_seed');
    }

    public function getSep10JWTSigningKey(): string
    {
        return config('stellar.sep10.jwt_signing_key');
    }

    public function getJwtTimeout(): int
    {
        return config('stellar.sep10.jwt_timeout', 300);
    }

    public function isClientAttributionRequired(): bool
    {
        return config('stellar.sep10.client_attribution_required', false);
    }

    public function getAllowedClientDomains(): ?array
    {
        return config('stellar.sep10.allowed_client_domains');
    }

    public function getKnownCustodialAccountList(): ?array
    {
        return config('stellar.sep10.known_custodial_accounts');
    }
}
