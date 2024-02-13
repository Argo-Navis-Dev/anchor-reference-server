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
use Soneso\StellarSDK\SEP\Toml\Currencies;
use Soneso\StellarSDK\SEP\Toml\Currency;
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
        $generalInfo->webAuthEndpoint = "https://localhost:5173/auth";
        $generalInfo->kYCServer = "https://localhost:5173/";
        $generalInfo->transferServerSep24 = "https://localhost:5173/sep24";
        $generalInfo->signingKey = "GCAT3G32LQV2V3WHRMKXLFAQNOCQXTUPUQXOXSTLSLSCLIVQP2NRQQ3T";
        $tomlData->generalInformation = $generalInfo;

        $currencyART = new Currency();
        $currencyART->code = 'ART';
        $currencyART->issuer = 'GDD4AM7ZITM6VIJBF6GFA6GCYY5EKMZ77OKYCLWGQYXNAK3KABDUOART';
        $currencyART->status = 'test';
        $currencyART->isAssetAnchored = false;
        $currencyART->desc = 'Argo Navis Reference Token (ART) is an asset issued on testnet and is used as an anchored asset for this reference server for demonstration and testing purposes.';

        $currencyUSDC = new Currency();
        $currencyUSDC->code = 'USDC';
        $currencyUSDC->issuer = 'GBBD47IF6LWK7P7MDEVSCWR7DPUWV3NY3DTQEVFL4NAT4AQH3ZLLFLA5';
        $currencyUSDC->status = 'test';
        $currencyUSDC->isAssetAnchored = false;
        $currencyUSDC->desc = 'Circle USDC Token';

        $currencies = new Currencies($currencyART, $currencyUSDC);
        $tomlData->currencies = $currencies;

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
