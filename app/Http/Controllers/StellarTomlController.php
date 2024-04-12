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
        $generalInfo->anchorQuoteServer = "https://localhost:5173/sep38";
        $generalInfo->signingKey = "GCAT3G32LQV2V3WHRMKXLFAQNOCQXTUPUQXOXSTLSLSCLIVQP2NRQQ3T";
        $generalInfo->accounts = [
            'GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2',
            'GAKRN7SCC7KVT52XLMOFFWOOM4LTI2TQALFKKJ6NKU3XWPNCLD5CFRY2',
            'GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U',
            'GCMMCKP2OJXLBZCANRHXSGMMUOGJQKNCHH7HQZ4G3ZFLAIBZY5ODJYO6',
            'GCAT3G32LQV2V3WHRMKXLFAQNOCQXTUPUQXOXSTLSLSCLIVQP2NRQQ3T'];
        $tomlData->generalInformation = $generalInfo;

        $currencyUSD = new Currency();
        $currencyUSD->code = 'USD';
        $currencyUSD->status = 'test';
        $currencyUSD->displayDecimals = 2;
        $currencyUSD->name = 'US Dollar fiat';
        $currencyUSD->desc = 'US Dollar fiat token for testing';
        $currencyUSD->isAssetAnchored = false;

        $currencyUSDC = new Currency();
        $currencyUSDC->code = 'USDC';
        $currencyUSDC->issuer = 'GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
        $currencyUSDC->status = 'test';
        $currencyUSDC->displayDecimals = 2;
        $currencyUSDC->name = 'US Dollar token on the chain';
        $currencyUSDC->desc = 'US Dollar on the chain for testing';
        $currencyUSDC->isAssetAnchored = true;
        $currencyUSDC->anchorAsset = 'USD';
        $currencyUSDC->redemptionInstructions = 'You can purchase the USDC token with USD, JYPC or native stellar lumens. You can sell it for USD or JYPC';

        $currencyJYPC = new Currency();
        $currencyJYPC->code = 'JPYC';
        $currencyJYPC->issuer = 'GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U';
        $currencyJYPC->status = 'test';
        $currencyJYPC->displayDecimals = 2;
        $currencyJYPC->name = 'Japan Yen token on the chain';
        $currencyJYPC->desc = 'Japan Yen on the chain for testing';
        $currencyJYPC->isAssetAnchored = true;
        $currencyJYPC->anchorAsset = 'JPY';
        $currencyJYPC->redemptionInstructions = 'You can purchase the JPYC token with USD, or USDC. You can sell it for USD or USDC';

        $currencies = new Currencies($currencyUSD, $currencyUSDC, $currencyJYPC);
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
