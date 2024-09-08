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
        $generalInfo->webAuthEndpoint = config('stellar.api.endpoints_base_url') . "/auth";
        $generalInfo->kYCServer = config('stellar.api.endpoints_base_url') . "/";
        $generalInfo->transferServer = config('stellar.api.endpoints_base_url') . "/sep06";
        $generalInfo->transferServerSep24 = config('stellar.api.endpoints_base_url') . "/sep24";
        $generalInfo->anchorQuoteServer = config('stellar.api.endpoints_base_url') . "/sep38";
        $generalInfo->directPaymentServer = config('stellar.api.endpoints_base_url') . "/sep31";
        $generalInfo->signingKey = config('stellar.server.server_account_id');
        $generalInfo->accounts = [
            config('stellar.server.server_account_id'),
            config('stellar.assets.usdc_asset_issuer_id'),
            config('stellar.assets.usdc_asset_distribution_account_id'),
            config('stellar.assets.jpyc_asset_issuer_id'),
            config('stellar.assets.jpyc_asset_distribution_account_id'),
            config('stellar.sep08.asset_issuer_id')];
        $tomlData->generalInformation = $generalInfo;

        $currencyUSD = new Currency();
        $currencyUSD->code = 'USD';
        $currencyUSD->status = 'test';
        $currencyUSD->displayDecimals = 2;
        $currencyUSD->name = 'US Dollar fiat';
        $currencyUSD->desc = 'US Dollar fiat token for testing';
        $currencyUSD->isAssetAnchored = false;

        $currencyUSDC = new Currency();
        $currencyUSDC->code = config('stellar.assets.usdc_asset_code');
        $currencyUSDC->issuer = config('stellar.assets.usdc_asset_issuer_id');
        $currencyUSDC->status = 'test';
        $currencyUSDC->displayDecimals = 2;
        $currencyUSDC->name = 'US Dollar token on the chain';
        $currencyUSDC->desc = 'US Dollar on the chain for testing';
        $currencyUSDC->isAssetAnchored = true;
        $currencyUSDC->anchorAsset = 'USD';
        $currencyUSDC->redemptionInstructions = 'You can purchase the USDC token with USD, JPYC or native stellar lumens. You can sell it for USD or JPYC';

        $currencyJPYC = new Currency();
        $currencyJPYC->code = config('stellar.assets.jpyc_asset_code');;
        $currencyJPYC->issuer = config('stellar.assets.jpyc_asset_issuer_id');
        $currencyJPYC->status = 'test';
        $currencyJPYC->displayDecimals = 2;
        $currencyJPYC->name = 'Japan Yen token on the chain';
        $currencyJPYC->desc = 'Japan Yen on the chain for testing';
        $currencyJPYC->isAssetAnchored = true;
        $currencyJPYC->anchorAsset = 'JPY';
        $currencyJPYC->redemptionInstructions = 'You can purchase the JPYC token with USD, or USDC. You can sell it for USD or USDC';

        $currencySTAR = new Currency();
        $currencySTAR->code = config('stellar.sep08.asset_code');
        $currencySTAR->issuer = config('stellar.sep08.asset_issuer_id');
        $currencySTAR->status = 'test';
        $currencySTAR->displayDecimals = 2;
        $currencySTAR->name = config('stellar.sep08.asset_toml_name');
        $currencySTAR->desc = config('stellar.sep08.asset_toml_desc');
        $currencySTAR->isAssetAnchored = false;
        $currencySTAR->regulated = true;
        $currencySTAR->approvalServer = config('stellar.sep08.asset_toml_approval_server');
        $currencySTAR->approvalCriteria = config('stellar.sep08.asset_toml_approval_criteria');

        $currencies = new Currencies($currencyUSD, $currencyUSDC, $currencyJPYC, $currencySTAR);
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
