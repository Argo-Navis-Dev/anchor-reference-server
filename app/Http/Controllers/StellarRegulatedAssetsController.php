<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Http\Controllers;

use App\Models\Sep08KycStatus;
use App\Stellar\Sep08RegulatedAssets\RegulatedAssetsIntegration;
use App\Stellar\StellarAppConfig;
use ArgoNavis\PhpAnchorSdk\Sep08\Sep08Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Soneso\StellarSDK\AllowTrustOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\AssetTypeCreditAlphanum;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Throwable;
use function PHPUnit\Framework\assertTrue;

/**
 * Handles the SEP-08 endpoints.
 */
class StellarRegulatedAssetsController extends Controller
{
    /**
     * This is the core SEP-8 endpoint used to validate and process regulated assets transactions.
     * @param ServerRequestInterface $request request from the client.
     * @return ResponseInterface response to the client
     */
    public function approve(ServerRequestInterface $request): ResponseInterface {
        $sep08Integration = new RegulatedAssetsIntegration();
        $sep08Service = new Sep08Service($sep08Integration, Log::getLogger());
        return $sep08Service->handleRequest($request);
    }

    /**
     * This endpoint is used for the extra action after /tx-approve, as described in the SEP-8 Action Required section.
     *
     * Currently, an arbitrary criteria is implemented:
     *
     * email addresses starting with "x" will have the KYC automatically denied.
     * email addresses starting with "y" will have their KYC marked as pending.
     * all other emails will be accepted.
     * Note: you'll need to resubmit your transaction to /tx_approve in order to verify if your KYC was approved.
     * @param Request $request request from the client containing the kyc data (email_address).
     * @param string $stellarAddress stellar address (account id) of the user.
     * @return JsonResponse response to the client.
     */
    public function setKycData(Request $request, string $stellarAddress) {
        try {
            $data = $request->json()->all();
            if (!is_array($data) || !isset($data['email_address'])) {
                $errorLabel = __('sep08_lang.action_required.missing_field', ['field_name' => 'email_address']);
                return response()->json(['message' => $errorLabel], 400);
            }
            $emailAddress = $data['email_address'];
            if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['message' => __('sep08_lang.action_required.invalid_email')], 400);
            }
            $kycData = Sep08KycStatus::whereStellarAddress($stellarAddress)->first();
            if ($kycData === null) {
                return response()->json(['message' => __('sep08_lang.action_required.kyc_data_not_found')], 404);
            }

            // As an arbitrary rule:
            // Emails starting with "x" are marked as rejected
            // Emails starting with "y" are marked as pending.
            // Emails not starting with "x" or "y" are marked as accepted.
            $kycData->rejected = str_starts_with($emailAddress, 'x');
            $kycData->pending = str_starts_with($emailAddress, 'y');
            $kycData->approved = (!str_starts_with($emailAddress, 'x') && !str_starts_with($emailAddress, 'y'));
            $kycData->save();

            return response()->json(['result' => 'no_further_action_required'], 200);

        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Returns the status of an account that requested KYC.
     *
     * Note: This functionality is for test/debugging purposes, and it's not part of the SEP-8 spec.
     * @param string $stellarAddress stellar address (account id) of the user.
     * @return JsonResponse response to the client.
     */
    public function getKycStatus(string $stellarAddress) {
        try {
            $kycData = Sep08KycStatus::whereStellarAddress($stellarAddress)->first();
            if ($kycData === null) {
                return response()->json(['message' => __('sep08_lang.action_required.kyc_data_not_found')], 404);
            } else {
                $result = ['address' => $stellarAddress];
                if ($kycData->rejected) {
                    $result += ['status' => 'rejected'];
                } else if ($kycData->approved) {
                    $result += ['status' => 'approved'];
                } else {
                    $result += ['status' => 'pending'];
                }
                return response()->json($result);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Deletes a stellar account from the list of KYCs. If the stellar address is not in the database
     * to be deleted the server will return with a 404 - Not Found.
     *
     * Note: This functionality is for test/debugging purposes and it's not part of the SEP-8 spec.
     *
     * @param string $stellarAddress stellar address (account id) of the user.
     * @return JsonResponse response to the client.
     */
    public function deleteKycAccount(string $stellarAddress) {
        try {
            $kycData = Sep08KycStatus::whereStellarAddress($stellarAddress)->first();
            if ($kycData === null) {
                return response()->json(['message' => __('sep08_lang.action_required.kyc_data_not_found')], 404);
            } else {
                $kycData->delete();
                return response()->json(['message' => 'ok'], 200);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * This endpoint sends a payment of 100 regulated assets to the provided addr.
     * GET /friendbot?addr=stellar_address
     * Please be aware the address must first establish a trustline to the regulated asset in order to receive
     * that payment. You can use Stellar Laboratory to do that.
     * @return JsonResponse response to the client.
     */
    public function friendbot() {

        $stellarAddress = request()->query('addr');
        if(!is_string($stellarAddress)) {
            return response()->json(['error' => __('sep08_lang.error.invalid_stellar_address')], 400);
        }
        $accountData = $this->getAccountDetails($stellarAddress);
        if ($accountData === null) {
            return response()->json(['error' => __('sep08_lang.error.account_not_exist')], 400);
        }
        $regulatedAsset = Asset::createFromCanonicalForm(
            config('stellar.sep08.asset_code') .':' .
            config('stellar.sep08.asset_issuer_id')
        );
        if (!($regulatedAsset instanceof AssetTypeCreditAlphanum)) {
            return response()->json(['error' => __('sep08_lang.error.asset_not_found')], 500);
        }

        $hasTrustline = false;

        foreach ($accountData->getBalances()->toArray() as $balance) {
            if($balance->getAssetCode() === $regulatedAsset->getCode() &&
                $balance->getAssetIssuer() === $regulatedAsset->getIssuer()) {
                $hasTrustline = true;
                break;
            }
        }
        if (!$hasTrustline) {
            $errorLabel = __(
                'sep08_lang.error.not_trust',
                ['code' => $regulatedAsset->getCode(), 'issuer' => $regulatedAsset->getIssuer()]
            );
            return response()->json(
                ['error' => $errorLabel],
                400
            );
        }
        $issuerAccountId = $regulatedAsset->getIssuer();
        $issuerAccount = $this->getAccountDetails($issuerAccountId);
        if($issuerAccount === null) {
            return response()->json(['error' => __('sep08_lang.error.issuer_account_not_found')],
                500
            );
        }

        $txBuilder = new TransactionBuilder($issuerAccount);
        $allowTrustOp = (
        new AllowTrustOperationBuilder(
            trustor: $stellarAddress,
            assetCode: $regulatedAsset->getCode(),
            authorized: true,
            authorizedToMaintainLiabilities: false,
        )
        )->setSourceAccount($issuerAccountId)->build();

        $paymentOp = (new PaymentOperationBuilder($stellarAddress, $regulatedAsset, '100'))->build();

        $disAllowTrustOp = (
        new AllowTrustOperationBuilder(
            trustor: $stellarAddress,
            assetCode: $regulatedAsset->getCode(),
            authorized: true,
            authorizedToMaintainLiabilities: false,
        )
        )->setSourceAccount($issuerAccountId)->build();

        $txBuilder->addOperation($allowTrustOp);
        $txBuilder->addOperation($paymentOp);
        $txBuilder->addOperation($disAllowTrustOp);

        $tx = $txBuilder->build();
        $issuerSeed = config('stellar.sep08.issuer_signing_key');
        $stellarConfig = new StellarAppConfig();
        $tx->sign(KeyPair::fromSeed($issuerSeed), $stellarConfig->getStellarNetwork());
        $sdk = new StellarSDK($stellarConfig->getHorizonUrl());
        $funded = false;
        try {
            $txResponse = $sdk->submitTransaction($tx);
            $funded = $txResponse->isSuccessful();
        } catch (HorizonRequestException $e) {
            $errorLabel = __('sep08_lang.error.regulated_asset_could_not_send', ['error' => $e->getMessage()]);
            return response()->json(['error' => $errorLabel],
                500
            );
        }
        if (!$funded) {
            $errorLabel = __('sep08_lang.error.regulated_asset_could_not_send', ['error' => '']);
            return response()->json(['error' => $errorLabel],
                500
            );
        }
        return response()->json(['message' => 'ok'], 200);
    }

    private function getAccountDetails(string $accountId): ?AccountResponse {
        try {
            $stellarConfig = new StellarAppConfig();
            $sdk = new StellarSDK($stellarConfig->getHorizonUrl());
            return $sdk->requestAccount($accountId);
        } catch(HorizonRequestException $e) {
            // account not found.
            return null;
        }
    }
}
