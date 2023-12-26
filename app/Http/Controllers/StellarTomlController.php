<?php

declare(strict_types=1);

// Copyright 2023 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use ArgoNavis\PhpAnchorSdk\Sep01\TomlData;
use ArgoNavis\PhpAnchorSdk\Sep01\TomlProvider;
use Psr\Http\Message\ResponseInterface;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Toml\Documentation;
use Soneso\StellarSDK\SEP\Toml\GeneralInformation;
use Soneso\StellarSDK\SEP\Toml\PointOfContact;
use Soneso\StellarSDK\SEP\Toml\Principals;

class StellarTomlController extends Controller
{
    //
    public function toml():ResponseInterface {
        $provider = new TomlProvider();
        return $provider->handleFromData(self::tomlData());
    }

    private function tomlData():TomlData {
        $tomlData = new TomlData();

        $generalInfo = new GeneralInformation();
        $generalInfo->version = "2.0.0";
        $generalInfo->networkPassphrase = Network::testnet()->getNetworkPassphrase();
        $generalInfo->webAuthEndpoint = "http://localhost:8000/webauth";
        $generalInfo->signingKey = "GCIFNTTPECZ4M2PXR76FTRDJM4AV7P2Q7275FL24BNFB56XCWMO53474";
        $tomlData->generalInformation = $generalInfo;

        $doc = new Documentation();
        $doc->orgName = "Argo Navis Dev";
        $doc->orgGithub = "https://github.com/Argo-Navis-Dev";
        $doc->orgUrl = "https://argo-navis.dev";
        $doc->orgDescription = 'Argo Navis Dev provides development services related to Stellar';
        $doc->orgOfficialEmail = 'info@argo-navis.dev';
        $tomlData->documentation = $doc;

        $principals = new Principals();
        $firstPoc = new PointOfContact();
        $firstPoc->name = 'Bence';
        $firstPoc->email = 'bence@argo-navis.dev';
        $principals->add($firstPoc);

        $secondPoc = new PointOfContact();
        $secondPoc->name = 'Christian';
        $secondPoc->email = 'christian@argo-navis.dev';
        $principals->add($secondPoc);
        $tomlData->principals = $principals;

        return $tomlData;
    }
}
