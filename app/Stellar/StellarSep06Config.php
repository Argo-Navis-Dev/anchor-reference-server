<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar;

use ArgoNavis\PhpAnchorSdk\config\ISep06Config;

class StellarSep06Config implements ISep06Config
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
        return true;
    }
}
