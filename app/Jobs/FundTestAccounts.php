<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Jobs;

use App\Stellar\Shared\SepHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;

/**
 * This job funds the accounts necessary to run this server demo on testnet.
 * This job is important for example after a Stellar testnet reset or if
 * distribution accounts for stellar anchor assets run out of funds.
 */
class FundTestAccounts implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    /**
     * The number of times the job may be attempted.
     * 0 = indefinitely
     * @var int
     */
    public $tries = 0;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 0;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //only on testnet
        $horizonUrl = config('stellar.app.horizon_url');
        if ($horizonUrl !== 'https://horizon-testnet.stellar.org') {
            return;
        }

        try {
            // The server account needs to exist on the stellar network. It also needs to hold Stellar lumens
            // to be able to fund receiver accounts that have deposit transactions but do not exist on the
            // stellar network.
            $serverAccountSeed = config('stellar.server.server_account_signing_key');
            $serverAccountKeyPair = KeyPair::fromSeed($serverAccountSeed);
            $serverAccountId = $serverAccountKeyPair->getAccountId();
            $this->fundTestAccount($serverAccountId);

            // The USDC anchor asset issuer account needs to exist on the stellar network. It also needs to hold Stellar
            // lumens to be able to fund the USDC anchor asset distribution account with USDC.
            $usdcIssuerAccountSeed = config('stellar.assets.usdc_asset_issuer_signing_key');
            $usdcIssuerKp = KeyPair::fromSeed($usdcIssuerAccountSeed);
            $usdcIssuerId = $usdcIssuerKp->getAccountId();
            $this->fundTestAccount($usdcIssuerId);
            // we need to set the home domain to the issuer account, so that clients can find the stellar.toml file.
            $this->setHomeDomainToAccount($usdcIssuerKp, config('stellar.server.server_home_domain'));

            // The USDC anchor asset distribution account needs to exist on the stellar network. It also needs to hold
            // Stellar lumens to be able to send payments and claimable balances for deposits.
            $usdcDistributionAccountSeed = config('stellar.assets.usdc_asset_distribution_signing_key');
            $usdcDistributionAccountKp = KeyPair::fromSeed($usdcDistributionAccountSeed);
            $this->fundTestAccount($usdcDistributionAccountKp->getAccountId());

            // Let the USDC anchor asset issuer account fund the USDC distribution account with enough USDC for testing.
            $usdcAssetCode = config('stellar.assets.usdc_asset_code');
            $this->distributeTestAsset(
                $usdcIssuerKp,
                $usdcDistributionAccountKp,
                Asset::createNonNativeAsset($usdcAssetCode, $usdcIssuerId),
            );

            // The JPYC anchor asset issuer account needs to exist on the stellar network. It also needs to hold Stellar
            // lumens to be able to fund the JPYC anchor asset distribution account with JPYC.
            $jpycIssuerAccountSeed = config('stellar.assets.jpyc_asset_issuer_signing_key');
            $jpycIssuerKp = KeyPair::fromSeed($jpycIssuerAccountSeed);
            $jpycIssuerId = $jpycIssuerKp->getAccountId();
            $this->fundTestAccount($jpycIssuerId);
            // we need to set the home domain to the issuer account, so that clients can find the stellar.toml file.
            $this->setHomeDomainToAccount($jpycIssuerKp, config('stellar.server.server_home_domain'));

            // The JPYC anchor asset distribution account needs to exist on the stellar network. It also needs to hold
            // Stellar lumens to be able to send payments and claimable balances for deposits.
            $jpycDistributionAccountSeed = config('stellar.assets.jpyc_asset_distribution_signing_key');
            $jpycDistributionAccountKp = KeyPair::fromSeed($jpycDistributionAccountSeed);
            $this->fundTestAccount($jpycDistributionAccountKp->getAccountId());

            // Let the JPYC anchor asset issuer account fund the JPYC distribution account with enough JPYC for testing.
            $jpycAssetCode = config('stellar.assets.jpyc_asset_code');
            $this->distributeTestAsset(
                $jpycIssuerKp,
                $jpycDistributionAccountKp,
                Asset::createNonNativeAsset($jpycAssetCode, $jpycIssuerId),
            );

            $sep08IssuerSigningKey = config('stellar.sep08.issuer_signing_key');
            $sep08IssuerAccountKp = KeyPair::fromSeed($sep08IssuerSigningKey);
            $this->fundTestAccount($sep08IssuerAccountKp->getAccountId());
            $this->setHomeDomainToAccount($sep08IssuerAccountKp, config('stellar.server.server_home_domain'));

        } catch (HorizonRequestException $e) {
            SepHelper::logHorizonRequestException($e, context: ['Job:FundTestAccounts']);
        } catch (Exception $e) {
            Log::error(message: $e->getTraceAsString() . PHP_EOL, context: ['Job:FundTestAccounts']);
        }
    }

    /**
     * Funds a testnet account using the testnet friendbot if the account dose not already exists.
     * If the account already exists and has less the 1000 Stellar lumens, it creates a new
     * temporary stellar testnet account using friendbot and then merges the new temporary
     * account into our account that needs more lumens.
     *
     * @param string $accountId the account to be funded on testnet
     * @return void
     * @throws HorizonRequestException if any horizon error occurs during the communication with horizon.
     */
    private function fundTestAccount(string $accountId): void
    {
        $stellarSDK = StellarSDK::getTestNetInstance();
        if (!$stellarSDK->accountExists($accountId)) {
            // account does not exist, so we can create it by using friendbot.
            FriendBot::fundTestAccount($accountId);
            return;
        }
        // request account data to see if we have enough Stellar lumens (XLM).
        $account = $stellarSDK->requestAccount($accountId);
        $xlmBalance = "0.0";
        foreach ($account->getBalances() as $balance) {
            if ($balance->getAssetType() === 'native') {
                $xlmBalance = $balance->getBalance();
                break;
            }
        }

        if (floatval($xlmBalance) < 1000) {
            // we should charge
            $newTmpAccountKp = KeyPair::random();
            $newTmpAccountId = $newTmpAccountKp->getAccountId();

            // fund the new account using friendbot
            FriendBot::fundTestAccount($newTmpAccountId);

            // merge the new account into our account so that we get its funds.
            $fundingAccount = $stellarSDK->requestAccount($newTmpAccountId);
            $mergeAccountOp = (new AccountMergeOperationBuilder(destinationAccountId: $accountId))->build();
            $tx  = (new TransactionBuilder($fundingAccount))->setMaxOperationFee(1000)
                ->addOperation($mergeAccountOp)->build();
            $tx->sign($newTmpAccountKp, Network::testnet());
            $stellarSDK->submitTransaction($tx);
        }
    }

    /**
     * Charges a distribution account with the given asset from the issuer account.
     *
     * @param KeyPair $issuerKp signing keypair of the issuer account.
     * @param KeyPair $distributionAccountKp signing keypair of the distribution account (needed for trustline)
     * @param AssetTypeCreditAlphanum $asset the asset to be sent.
     * @return void
     * @throws HorizonRequestException if any horizon error occurs during the communication with horizon.
     */
    private function distributeTestAsset(
        KeyPair $issuerKp,
        KeyPair $distributionAccountKp,
        AssetTypeCreditAlphanum $asset,
    ) : void {
        if ($asset->getIssuer() !== $issuerKp->getAccountId()) {
            Log::error(
                message: 'Invalid asset issuer : ' . $asset->getIssuer() . PHP_EOL,
                context: ['Job:FundTestAccounts', 'distributeTestAsset'],
            );
            return;
        }
        $stellarSDK = StellarSDK::getTestNetInstance();

        $distributionAccountId = $distributionAccountKp->getAccountId();
        $distributionAccount = $stellarSDK->requestAccount($distributionAccountId);

        // check if the distribution account already trusts the asset
        $trustlineNeeded = true;
        $assetBalance = "0.0";
        foreach ($distributionAccount->getBalances() as $balance) {
            if($balance->getAssetCode() === $asset->getCode() &&
            $balance->getAssetIssuer() === $asset->getIssuer()) {
                $assetBalance = $balance->getBalance();
                $trustlineNeeded = false;
                break;
            }
        }
        if ($trustlineNeeded) {
            // the distribution account has no trustline to the asset, so we need to create one first
            // to be able to receive the funds.
            $changeTrustOp = (new ChangeTrustOperationBuilder(asset: $asset))->build();
            $tx  = (new TransactionBuilder($distributionAccount))->setMaxOperationFee(1000)
                ->addOperation($changeTrustOp)->build();
            $tx->sign($distributionAccountKp, Network::testnet());
            $stellarSDK->submitTransaction($tx);
        }

        if (floatval($assetBalance) < 100000) {
            // we need to charge
            $issuerAccount = $stellarSDK->requestAccount($issuerKp->getAccountId());
            $paymentOp = (New PaymentOperationBuilder($distributionAccountId, $asset, '1000000000'))->build();
            $tx  = (new TransactionBuilder($issuerAccount))->setMaxOperationFee(1000)
                ->addOperation($paymentOp)->build();
            $tx->sign($issuerKp, Network::testnet());
            $stellarSDK->submitTransaction($tx);
        }
    }

    /**
     * Sets the home domain to a give (issuer) account, so that clients can find the stellar.toml. file.
     * @param KeyPair $accountKeyPair the signing keypair of the account to set the home domain for
     * @param string $homeDomain the home domain to be set.
     * @return void
     * @throws HorizonRequestException if any horizon exception occurs during communication with horizon.
     */
    private function setHomeDomainToAccount(Keypair $accountKeyPair, string $homeDomain): void {
        $stellarSDK = StellarSDK::getTestNetInstance();
        $accountId = $accountKeyPair->getAccountId();
        $account = $stellarSDK->requestAccount($accountId);

        // check if the account already has the home domain set
        $currentHomeDomain = $account->getHomeDomain();
        if ($currentHomeDomain === $homeDomain) {
            return;
        }

        $setOptionsOp = (new SetOptionsOperationBuilder())->setHomeDomain($homeDomain)->build();
        $tx = (new TransactionBuilder($account))
            ->addOperation($setOptionsOp)
            ->build();
        $tx->sign($accountKeyPair, Network::testnet());
        $stellarSDK->submitTransaction($tx);
    }
}
