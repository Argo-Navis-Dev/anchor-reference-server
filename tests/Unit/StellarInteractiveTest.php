<?php

namespace Tests\Unit;

use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Interactive\FeatureFlags;
use Soneso\StellarSDK\SEP\Interactive\FeeEndpointInfo;
use Soneso\StellarSDK\SEP\Interactive\InteractiveService;
use Soneso\StellarSDK\SEP\Interactive\SEP24DepositRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24FeeRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;

class StellarInteractiveTest extends TestCase
{

    private string $domain = 'localhost:5173';
    public ?FeeEndpointInfo $feeEndpointInfo = null;
    public ?FeatureFlags $featureFlags = null;

    public function testGetInfo() {
        $interactiveService = $this->getInteractiveService();
        $response = $interactiveService->info();
        self::assertCount(2, $response->depositAssets);
        self::assertCount(2, $response->withdrawAssets);

        assertNotNull($response->feeEndpointInfo);
        $feeEndpointInfo = $response->feeEndpointInfo;
        self::assertTrue($feeEndpointInfo->enabled);
        self::assertFalse($feeEndpointInfo->authenticationRequired);

        assertNotNull($response->featureFlags);
        $featureFlags = $response->featureFlags;
        self::assertFalse($featureFlags->accountCreation);
        self::assertFalse($featureFlags->claimableBalances);

        $depositAssets = $response->depositAssets;
        $artDepositAsset = null;
        $usdcDepositAsset = null;
        foreach (array_keys($depositAssets) as $key) {
            if ($key === 'ART') {
                $artDepositAsset = $depositAssets[$key];
            } elseif ($key === 'USDC') {
                $usdcDepositAsset = $depositAssets[$key];
            }
        }
        self::assertNotNull($artDepositAsset);
        self::assertNotNull($usdcDepositAsset);

        self::assertTrue($artDepositAsset->enabled);
        self::assertNull($artDepositAsset->feePercent);
        self::assertNull($artDepositAsset->feeMinimum);
        self::assertEquals(1.0, $artDepositAsset->feeFixed);
        self::assertNull($artDepositAsset->minAmount);
        self::assertNull($artDepositAsset->maxAmount);

        self::assertTrue($usdcDepositAsset->enabled);
        self::assertNull($usdcDepositAsset->feePercent);
        self::assertNull($usdcDepositAsset->feeMinimum);
        self::assertNull($usdcDepositAsset->feeFixed);
        self::assertNull($usdcDepositAsset->minAmount);
        self::assertEquals(1000.0, $usdcDepositAsset->maxAmount);


        $withdrawAssets = $response->withdrawAssets;
        $artWithdrawAsset = null;
        $usdcWithdrawAsset = null;
        foreach (array_keys($withdrawAssets) as $key) {
            if ($key === 'ART') {
                $artWithdrawAsset = $withdrawAssets[$key];
            } elseif ($key === 'USDC') {
                $usdcWithdrawAsset = $withdrawAssets[$key];
            }
        }
        self::assertNotNull($artWithdrawAsset);
        self::assertNotNull($usdcWithdrawAsset);

        self::assertTrue($artWithdrawAsset->enabled);
        self::assertNull($artWithdrawAsset->feePercent);
        self::assertNull($artWithdrawAsset->feeMinimum);
        self::assertEquals(1.0, $artWithdrawAsset->feeFixed);
        self::assertNull($artWithdrawAsset->minAmount);
        self::assertNull($artWithdrawAsset->maxAmount);

        self::assertTrue($usdcWithdrawAsset->enabled);
        self::assertNull($usdcWithdrawAsset->feePercent);
        self::assertNull($usdcWithdrawAsset->feeMinimum);
        self::assertNull($usdcWithdrawAsset->feeFixed);
        self::assertNull($usdcWithdrawAsset->minAmount);
        self::assertNull($usdcWithdrawAsset->maxAmount);

    }

    public function testFee() {
        $interactiveService = $this->getInteractiveService();
        $feeRequest = new SEP24FeeRequest(
            operation: 'deposit',
            assetCode: 'ART',
            amount: 100.0,
        );

        $feeResponse = $interactiveService->fee($feeRequest);
        assertNotNull($feeResponse->fee);
        assertEquals(1.0, $feeResponse->fee);

        $feeRequest = new SEP24FeeRequest(
            operation: 'withdraw',
            assetCode: 'ART',
            amount: 100.0,
        );

        $feeResponse = $interactiveService->fee($feeRequest);
        assertNotNull($feeResponse->fee);
        assertEquals(1.0, $feeResponse->fee);

        $feeRequest = new SEP24FeeRequest(
            operation: 'deposit',
            assetCode: 'USDC',
            amount: 100.0,
        );

        $feeResponse = $interactiveService->fee($feeRequest);
        assertNotNull($feeResponse->fee);
        assertEquals(0.0, $feeResponse->fee);

        $feeRequest = new SEP24FeeRequest(
            operation: 'withdraw',
            assetCode: 'USDC',
            amount: 100.0,
        );

        $feeResponse = $interactiveService->fee($feeRequest);
        assertNotNull($feeResponse->fee);
        assertEquals(0.0, $feeResponse->fee);
    }

    public function testDepositAndWithdraw() {
        // create a new stellar account
        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();

        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($userKeyPair);
        $interactiveService = $this->getInteractiveService();

        // deposit request
        $depositRequest = new SEP24DepositRequest();
        $depositRequest->jwt = $jwtToken;
        $depositRequest->assetCode = 'ART';
        $depositRequest->sourceAsset = (new IdentificationFormatAsset(
            'stellar',
            IdentificationFormatAsset::NATIVE_ASSET_CODE
        ))->getStringRepresentation();
        $depositRequest->amount = 100.0;

        // add some kyc data
        $naturalPersonFields = new NaturalPersonKYCFields();
        $naturalPersonFields->firstName = "John";
        $naturalPersonFields->lastName = "Doe";
        $filePath = 'tests/files/id_front.png';
        if (str_ends_with(getcwd(), 'Unit')) {
            $filePath = '../files/id_front.png';
        }
        $naturalPersonFields->photoIdFront = file_get_contents($filePath, false);
        $naturalPersonFields->emailAddress = "testuser@stellargate.com";
        $kyc = new StandardKYCFields();
        $kyc->naturalPersonKYCFields = $naturalPersonFields;
        $depositRequest->kycFields = $kyc;

        $depositResponse = $interactiveService->deposit($depositRequest);
        print($depositResponse->id . PHP_EOL);
        assertEquals('interactive_customer_info_needed', $depositResponse->type);

        // check if transaction has been added
        $txRequest = new SEP24TransactionRequest();
        $txRequest->jwt = $jwtToken;
        $txRequest->id = $depositResponse->id;
        $tx = $interactiveService->transaction($txRequest);
        assertEquals($depositResponse->id, $txRequest->id);

        // check transactions history
        $txHistoryRequest = new SEP24TransactionsRequest();
        $txHistoryRequest->jwt = $jwtToken;
        $txHistoryRequest->assetCode = 'ART';
        $txs = $interactiveService->transactions($txHistoryRequest);
        self::assertCount(1, $txs->transactions);

        // check if user has been created
        $kycService = $this->getKycService();
        $getInfoRequest = new GetCustomerInfoRequest();
        $getInfoRequest->account = $userAccountId;
        $getInfoRequest->jwt = $jwtToken;
        $customerInfoResponse = $kycService->getCustomerInfo($getInfoRequest);
        assertEquals(CustomerStatus::NEEDS_INFO, $customerInfoResponse->getStatus());
        $fields = $customerInfoResponse->getFields();
        assertNotNull($fields);
        $providedFields = $customerInfoResponse->getProvidedFields();
        assertNotNull($providedFields);
        $emailField = $providedFields['email_address'];
        assertEquals(ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED, $emailField->getStatus());

        // withdraw request
        $withdrawRequest = new SEP24WithdrawRequest();
        $withdrawRequest->jwt = $jwtToken;
        $withdrawRequest->assetCode = 'ART';
        $withdrawRequest->destinationAsset = (new IdentificationFormatAsset(
            'stellar',
            IdentificationFormatAsset::NATIVE_ASSET_CODE
        ))->getStringRepresentation();
        $withdrawRequest->amount = 120.0;

        // update the kyc data
        $naturalPersonFields = new NaturalPersonKYCFields();
        $naturalPersonFields->lastName = "Doe2";
        $naturalPersonFields->idNumber = "91283763";
        $naturalPersonFields->idType = "Passport";
        $kyc->naturalPersonKYCFields = $naturalPersonFields;
        $withdrawRequest->kycFields = $kyc;

        $withdrawResponse = $interactiveService->withdraw($withdrawRequest);
        print($withdrawResponse->id . PHP_EOL);
        assertEquals('interactive_customer_info_needed', $withdrawResponse->type);

        // check if transaction has been added
        $txRequest->id = $withdrawResponse->id;
        $tx = $interactiveService->transaction($txRequest);
        assertEquals($withdrawResponse->id, $txRequest->id);

        // check transactions history
        $txs = $interactiveService->transactions($txHistoryRequest);
        self::assertCount(2, $txs->transactions);

        // check if customer has been updated
        $customerInfoResponse = $kycService->getCustomerInfo($getInfoRequest);
        $providedFields = $customerInfoResponse->getProvidedFields();
        assertNotNull($providedFields);
        assertNotNull($providedFields['id_type']);
        assertEquals(ProvidedCustomerFieldStatus::PROCESSING, $providedFields['id_type']->getStatus());
    }

    private function getInteractiveService() : InteractiveService {
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        $stellarToml = StellarToml::fromDomain($this->domain, $client);
        $address = $stellarToml->getGeneralInformation()->transferServerSep24;
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        return new InteractiveService(serviceAddress: $address, httpClient: $client);
    }

    private function getKycService() : KYCService {
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        return KYCService::fromDomain($this->domain, httpClient: $client);
    }

    private function getJwtToken(KeyPair $keyPair): string {
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        $stellarToml = StellarToml::fromDomain($this->domain, $client);
        $generalInformation = $stellarToml->getGeneralInformation();
        $this->assertNotNull($generalInformation);
        $this->assertNotNull($generalInformation->kYCServer);
        $this->assertNotNull($generalInformation->webAuthEndpoint);

        $webAuth = WebAuth::fromDomain($this->domain, network: Network::testnet(), httpClient: $client);
        $jwtToken = $webAuth->jwtToken($keyPair->getAccountId(), [$keyPair]);

        $this->assertNotNull($jwtToken);
        return $jwtToken;
    }
}
