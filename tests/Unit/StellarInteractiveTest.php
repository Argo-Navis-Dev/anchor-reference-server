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
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24TransactionsRequest;
use Soneso\StellarSDK\SEP\Interactive\SEP24WithdrawRequest;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
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
        self::assertCount(4, $response->depositAssets);
        self::assertCount(4, $response->withdrawAssets);

        assertNotNull($response->feeEndpointInfo);
        $feeEndpointInfo = $response->feeEndpointInfo;
        self::assertFalse($feeEndpointInfo->enabled);
        self::assertFalse($feeEndpointInfo->authenticationRequired);

        assertNotNull($response->featureFlags);
        $featureFlags = $response->featureFlags;
        self::assertTrue($featureFlags->accountCreation);
        self::assertFalse($featureFlags->claimableBalances);

        $depositAssets = $response->depositAssets;
        $usdcDepositAsset = null;
        $jpycDepositAsset = null;
        $usdDepositAsset = null;
        $nativeDepositAsset = null;
        foreach (array_keys($depositAssets) as $key) {
            if ($key === 'USDC') {
                $usdcDepositAsset = $depositAssets[$key];
            } elseif ($key === 'JPYC') {
                $jpycDepositAsset = $depositAssets[$key];
            } elseif ($key === 'USD') {
                $usdDepositAsset = $depositAssets[$key];
            } elseif ($key === 'native') {
                $nativeDepositAsset = $depositAssets[$key];
            }
        }

        self::assertNotNull($usdcDepositAsset);
        self::assertNotNull($jpycDepositAsset);
        self::assertNotNull($usdDepositAsset);
        self::assertNotNull($nativeDepositAsset);

        self::assertTrue($usdcDepositAsset->enabled);
        self::assertNull($usdcDepositAsset->feePercent);
        self::assertNull($usdcDepositAsset->feeMinimum);
        self::assertNull($usdcDepositAsset->feeFixed);
        self::assertEquals(1.0, $usdcDepositAsset->minAmount);
        self::assertEquals(1000.0, $usdcDepositAsset->maxAmount);


        $withdrawAssets = $response->withdrawAssets;
        $usdcWithdrawAsset = null;
        $jpycWithdrawAsset = null;
        $usdWithdrawAsset = null;
        $nativeWithdrawAsset = null;
        foreach (array_keys($withdrawAssets) as $key) {
            if ($key === 'USDC') {
                $usdcWithdrawAsset = $withdrawAssets[$key];
            } elseif ($key === 'JPYC') {
                $jpycWithdrawAsset = $withdrawAssets[$key];
            } elseif ($key === 'USD') {
                $usdWithdrawAsset = $withdrawAssets[$key];
            } elseif ($key === 'native') {
                $nativeWithdrawAsset = $withdrawAssets[$key];
            }
        }
        self::assertNotNull($usdcWithdrawAsset);
        self::assertNotNull($jpycWithdrawAsset);
        self::assertNotNull($usdWithdrawAsset);
        self::assertNotNull($nativeWithdrawAsset);

        self::assertTrue($usdcWithdrawAsset->enabled);
        self::assertNull($usdcWithdrawAsset->feePercent);
        self::assertNull($usdcWithdrawAsset->feeMinimum);
        self::assertNull($usdcWithdrawAsset->feeFixed);
        self::assertEquals(1.0, $usdcWithdrawAsset->minAmount);
        self::assertEquals(1000.0, $usdcWithdrawAsset->maxAmount);

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
        $depositRequest->assetCode = 'USDC';
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
        assertEquals($tx->transaction->id, $txRequest->id);

        // check transactions history
        $txHistoryRequest = new SEP24TransactionsRequest();
        $txHistoryRequest->jwt = $jwtToken;
        $txHistoryRequest->assetCode = 'USDC';
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
        $withdrawRequest->assetCode = 'USDC';
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
        assertEquals($tx->transaction->id, $txRequest->id);

        // check transactions history
        $txs = $interactiveService->transactions($txHistoryRequest);
        self::assertCount(2, $txs->transactions);

        // check if customer has been updated
        $customerInfoResponse = $kycService->getCustomerInfo($getInfoRequest);
        $providedFields = $customerInfoResponse->getProvidedFields();
        assertNotNull($providedFields);
        assertNotNull($providedFields['id_type']);
        assertEquals(ProvidedCustomerFieldStatus::ACCEPTED, $providedFields['id_type']->getStatus());
    }

    public function testDepositAndWithdrawWithQuote() {
        // create a new stellar account
        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();

        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($userKeyPair);

        $usdcAsset = 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
        $usdAsset = 'iso4217:USD';

        // deposit

        $request = new SEP38PostQuoteRequest(
            context: 'sep6',
            sellAsset: $usdAsset,
            buyAsset: $usdcAsset,
            sellAmount: "1000",
        );
        $quotesService = $this->getQuotesService();
        $quote = $quotesService->postQuote($request, $jwtToken);

        $interactiveService = $this->getInteractiveService();

        $depositRequest = new SEP24DepositRequest();
        $depositRequest->jwt = $jwtToken;
        $depositRequest->assetCode = 'USDC';
        //$depositRequest->assetIssuer = 'GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
        $depositRequest->sourceAsset = $usdAsset;
        $depositRequest->amount = 1000.0;
        $depositRequest->quoteId = $quote->id;

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
        assertEquals($tx->transaction->id, $txRequest->id);

        // check transactions history
        $txHistoryRequest = new SEP24TransactionsRequest();
        $txHistoryRequest->jwt = $jwtToken;
        $txHistoryRequest->assetCode = 'USDC';
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

        // withdraw

        $request = new SEP38PostQuoteRequest(
            context: 'sep6',
            sellAsset: $usdcAsset,
            buyAsset: $usdAsset,
            sellAmount: "120",
        );

        $quote = $quotesService->postQuote($request, $jwtToken);

        $withdrawRequest = new SEP24WithdrawRequest();
        $withdrawRequest->jwt = $jwtToken;
        $withdrawRequest->assetCode = 'USDC';
        //$withdrawRequest->assetIssuer = 'GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
        $withdrawRequest->destinationAsset = $usdAsset;
        $withdrawRequest->amount = 120.0;
        $withdrawRequest->quoteId = $quote->id;

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
        assertEquals($tx->transaction->id, $txRequest->id);

        // check transactions history
        $txs = $interactiveService->transactions($txHistoryRequest);
        self::assertCount(2, $txs->transactions);

        // check if customer has been updated
        $customerInfoResponse = $kycService->getCustomerInfo($getInfoRequest);
        $providedFields = $customerInfoResponse->getProvidedFields();
        assertNotNull($providedFields);
        assertNotNull($providedFields['id_type']);
        assertEquals(ProvidedCustomerFieldStatus::ACCEPTED, $providedFields['id_type']->getStatus());
    }


    /*
    public function testFee() {
        $interactiveService = $this->getInteractiveService();
        $feeRequest = new SEP24FeeRequest(
            operation: 'deposit',
            assetCode: 'USDC',
            amount: 100.0,
        );

        $feeResponse = $interactiveService->fee($feeRequest);
        assertNotNull($feeResponse->fee);
        assertEquals(1.0, $feeResponse->fee);

        $feeRequest = new SEP24FeeRequest(
            operation: 'withdraw',
            assetCode: 'USDC',
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
    } */
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

    private function getQuotesService() : QuoteService {
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        $stellarToml = StellarToml::fromDomain($this->domain, $client);
        $address = $stellarToml->getGeneralInformation()->anchorQuoteServer;
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        return new QuoteService(serviceAddress: $address, httpClient: $client);
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
