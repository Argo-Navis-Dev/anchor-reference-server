<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\ISep10Config;

class StellarSep10Config implements ISep10Config
{

    /**
     * @inheritDoc
     */
    public function getWebAuthDomain(): ?string
    {
        return config('stellar.sep10.web_auth_domain');
    }

    /**
     * @inheritDoc
     */
    public function getHomeDomains(): array
    {
        return config('stellar.sep10.home_domains');
    }

    /**
     * @inheritDoc
     */
    public function getAuthTimeout(): int
    {
        return config('stellar.sep10.auth_timeout', 300);
    }

    /**
     * @inheritDoc
     */
    public function getSep10SigningSeed(): string
    {
        return config('stellar.sep10.server_signing_seed');
    }

    /**
     * @inheritDoc
     */
    public function getSep10JWTSigningKey(): string
    {
        return config('stellar.sep10.jwt_signing_key');
    }

    /**
     * @inheritDoc
     */
    public function getJwtTimeout(): int
    {
        return config('stellar.sep10.jwt_timeout', 900);
    }

    /**
     * @inheritDoc
     */
    public function isClientAttributionRequired(): bool
    {
        return config('stellar.sep10.client_attribution_required', false);
    }

    /**
     * @inheritDoc
     */
    public function getAllowedClientDomains(): ?array
    {
        return config('stellar.sep10.allowed_client_domains');
    }

    /**
     * @inheritDoc
     */
    public function getKnownCustodialAccountList(): ?array
    {
        return config('stellar.sep10.known_custodial_accounts');
    }
}
