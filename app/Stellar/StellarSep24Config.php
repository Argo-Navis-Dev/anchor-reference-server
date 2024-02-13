<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\ISep24Config;

class StellarSep24Config implements ISep24Config
{

    /**
     * @inheritDoc
     */
    public function isAccountCreationSupported(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function areClaimableBalancesSupported(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function isFeeEndpointSupported(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function feeEndpointRequiresAuthentication(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function shouldSdkCalculateObviousFee(): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getUploadFileMaxSizeMb(): ?int
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function getUploadFileMaxCount(): ?int
    {
        return null;
    }
}
