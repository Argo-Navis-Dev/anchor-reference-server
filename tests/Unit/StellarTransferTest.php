<?php

namespace Tests\Unit;

use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorField;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionRequest;
use Soneso\StellarSDK\SEP\TransferServerService\AnchorTransactionsRequest;
use Soneso\StellarSDK\SEP\TransferServerService\DepositAsset;
use Soneso\StellarSDK\SEP\TransferServerService\DepositExchangeAsset;
use Soneso\StellarSDK\SEP\TransferServerService\DepositExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\DepositRequest;
use Soneso\StellarSDK\SEP\TransferServerService\TransferServerService;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawAsset;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawExchangeAsset;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawExchangeRequest;
use Soneso\StellarSDK\SEP\TransferServerService\WithdrawRequest;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;

use Soneso\StellarSDK\Util\FriendBot;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertGreaterThan;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertNull;
use function PHPUnit\Framework\assertStringContainsString;
use function PHPUnit\Framework\assertTrue;
use const E_ALL;
use function error_reporting;

class StellarTransferTest extends TestCase
{
    private string $domain = 'localhost:5173';
    private KeyPair $userKeyPair;
    private string $userAccountId;
    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
        $this->userKeyPair = KeyPair::random();
        $this->userAccountId = $this->userKeyPair->getAccountId();
        FriendBot::fundTestAccount($this->userAccountId);
    }

    public function testGetInfo()
    {
        $transferService = $this->getTransferServerService();
        $response = $transferService->info();
        assertCount(2, $response->depositAssets);
        assertCount(2, $response->depositExchangeAssets);
        assertCount(2, $response->withdrawAssets);
        assertCount(2, $response->withdrawExchangeAssets);

        assertNotNull($response->feeInfo);
        $feeInfo = $response->feeInfo;
        assertFalse($feeInfo->enabled);
        assertEquals("Fee endpoint is not supported.", $feeInfo->description);

        assertNotNull($response->transactionInfo);
        $transactionInfo= $response->transactionInfo;
        assertTrue($transactionInfo->enabled);
        assertTrue($transactionInfo->authenticationRequired);

        assertNotNull($response->transactionsInfo);
        $transactionsInfo= $response->transactionsInfo;
        assertTrue($transactionsInfo->enabled);
        assertTrue($transactionsInfo->authenticationRequired);

        assertNotNull($response->featureFlags);
        $featureFlags = $response->featureFlags;
        assertTrue($featureFlags->accountCreation);
        assertTrue($featureFlags->claimableBalances);

        $depositAssetUSD = $response->depositAssets["USDC"];
        assertNotNull($depositAssetUSD);
        assertTrue($depositAssetUSD instanceof DepositAsset);
        assertTrue($depositAssetUSD->enabled);
        assertTrue($depositAssetUSD->authenticationRequired);
        assertNull($depositAssetUSD->feeFixed);
        assertNull($depositAssetUSD->feePercent);
        assertEquals(1, $depositAssetUSD->minAmount);
        assertEquals(1000.0, $depositAssetUSD->maxAmount);

        $fields = $depositAssetUSD->fields;
        assertNotNull($fields);
        $typeField = $fields['type'];
        assertNotNull($typeField);
        assertTrue($typeField instanceof AnchorField);
        assertEquals("type of deposit to make", $typeField->description);
        assertTrue(in_array("bank_account", $typeField->choices));

        $depositExchangeAssetUSD = $response->depositExchangeAssets["USDC"];
        assertNotNull($depositExchangeAssetUSD);
        assertTrue($depositExchangeAssetUSD instanceof DepositExchangeAsset);
        assertTrue($depositExchangeAssetUSD->enabled);
        assertTrue($depositExchangeAssetUSD->authenticationRequired);
        assertEquals(1, $depositExchangeAssetUSD->minAmount);
        assertEquals(1000.0, $depositExchangeAssetUSD->maxAmount);

        $fields = $depositExchangeAssetUSD->fields;
        assertNotNull($fields);
        $typeField = $fields['type'];
        assertNotNull($typeField);
        assertTrue($typeField instanceof AnchorField);
        assertEquals("type of deposit to make", $typeField->description);
        assertTrue(in_array("bank_account", $typeField->choices));

        $withdrawAssetUSD = $response->withdrawAssets["USDC"];
        assertNotNull($withdrawAssetUSD);
        assertTrue($withdrawAssetUSD instanceof WithdrawAsset);
        assertTrue($withdrawAssetUSD->enabled);
        assertTrue($withdrawAssetUSD->authenticationRequired);
        assertNull($withdrawAssetUSD->feeFixed);
        assertNull($withdrawAssetUSD->feePercent);
        assertEquals(1, $withdrawAssetUSD->minAmount);
        assertEquals(1000.0, $withdrawAssetUSD->maxAmount);

        $types = $withdrawAssetUSD->types;
        assertNotNull($types);
        assertCount(1, $types);
        $wireFields = $types["bank_account"];
        assertNotNull($wireFields);

        $withdrawExchangeAssetUSD = $response->withdrawExchangeAssets["USDC"];
        assertNotNull($withdrawExchangeAssetUSD);
        assertTrue($withdrawExchangeAssetUSD instanceof WithdrawExchangeAsset);
        assertTrue($withdrawExchangeAssetUSD->enabled);
        assertTrue($withdrawExchangeAssetUSD->authenticationRequired);
        assertEquals(1, $withdrawExchangeAssetUSD->minAmount);
        assertEquals(1000.0, $withdrawExchangeAssetUSD->maxAmount);

        $types = $withdrawExchangeAssetUSD->types;
        assertNotNull($types);
        assertCount(1, $types);
        $wireFields = $types["bank_account"];
        assertNotNull($wireFields);
    }


    public function testDepositAndWithdraw() {

        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $transferService = $this->getTransferServerService();

        $request = new DepositRequest(
            assetCode: "USDC",
            account: $this->userAccountId,
            type: 'bank_account',
            amount: "100",
            jwt: $jwtToken,
        );
        $response = $transferService->deposit($request);
        assertNotNull($response->id);
        $depositId = $response->id;
        assertEquals('Check the transaction for more information about how to deposit.', $response->how);

        $request = new AnchorTransactionRequest();
        $request->jwt = $jwtToken;
        $request->id = $depositId;
        $response = $transferService->transaction($request);
        $tx = $response->transaction;
        assertNotNull($tx);

        assertEquals($depositId, $tx->id);
        assertEquals('deposit', $tx->kind);
        assertEquals(Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE, $tx->status);
        assertNotNull($tx->instructions);
        assertCount(2, $tx->instructions);
        $bankAccount = $tx->instructions['bank_number'];
        assertEquals('121122676', $bankAccount->value);
        assertEquals('Fake bank number', $bankAccount->description);

        $request = new WithdrawRequest(
            assetCode: "USDC",
            type: "bank_account",
            account: $this->userAccountId,
            amount: "100.0",
            jwt: $jwtToken,
        );
        $response = $transferService->withdraw($request);
        assertNotNull($response->id);
        $withdrawId = $response->id;
        assertEquals('GAKRN7SCC7KVT52XLMOFFWOOM4LTI2TQALFKKJ6NKU3XWPNCLD5CFRY2', $response->accountId);
        assertEquals('id', $response->memoType);
        $withdrawMemo = $response->memo;
        assertNotNull($withdrawMemo);

        $request = new AnchorTransactionRequest();
        $request->jwt = $jwtToken;
        $request->id = $withdrawId;
        $response = $transferService->transaction($request);
        $tx = $response->transaction;
        assertNotNull($tx);

        assertEquals($withdrawId, $tx->id);
        assertEquals('withdraw', $tx->kind);
        assertEquals(Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE, $tx->status);
        assertEquals(100, floatval($tx->amountIn));
        assertEquals($withdrawMemo, $tx->withdrawMemo);
        assertEquals('id', $tx->withdrawMemoType);
        assertEquals('GAKRN7SCC7KVT52XLMOFFWOOM4LTI2TQALFKKJ6NKU3XWPNCLD5CFRY2', $tx->withdrawAnchorAccount);

    }

    public function testDepositAndWithdrawExchange() {

        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $transferService = $this->getTransferServerService();
        $usdFiat = IdentificationFormatAsset::fromString('iso4217:USD');
        $request = new DepositExchangeRequest(
            destinationAsset: "JPYC",
            sourceAsset: $usdFiat->getStringRepresentation(),
            amount: "100",
            account: $this->userAccountId,
            type: 'bank_account',
            jwt: $jwtToken,
        );
        $response = $transferService->depositExchange($request);
        assertNotNull($response->id);
        $depositExchangeId = $response->id;
        assertEquals('Check the transaction for more information about how to deposit.', $response->how);

        $request = new AnchorTransactionRequest();
        $request->jwt = $jwtToken;
        $request->id = $depositExchangeId;
        $response = $transferService->transaction($request);
        $tx = $response->transaction;
        assertNotNull($tx);

        assertEquals($depositExchangeId, $tx->id);
        assertEquals('deposit-exchange', $tx->kind);
        assertEquals(Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE, $tx->status);
        assertEquals(0, floatval($tx->amountIn));
        assertEquals($usdFiat->getStringRepresentation(), $tx->amountInAsset);
        assertStringContainsString('JPYC', $tx->amountOutAsset);
        $feeDetails = $tx->feeDetails;
        assertNotNull($feeDetails);
        assertEquals('0.1', $feeDetails->total);
        assertStringContainsString('JPYC', $feeDetails->asset);
        assertCount(1, $feeDetails->details);
        $detail = $feeDetails->details[0];
        assertEquals('Service fee', $detail->name);
        assertEquals('0.1', $detail->amount);

        assertNotNull($tx->instructions);
        assertCount(2, $tx->instructions);
        $bankAccount = $tx->instructions['bank_number'];
        assertEquals('121122676', $bankAccount->value);
        assertEquals('Fake bank number', $bankAccount->description);

        $request = new WithdrawExchangeRequest(
            sourceAsset: "JPYC",
            destinationAsset: $usdFiat->getStringRepresentation(),
            amount: "100.0",
            type: "bank_account",
            account: $this->userAccountId,
            jwt: $jwtToken,
        );
        $response = $transferService->withdrawExchange($request);
        assertNotNull($response->id);
        $withdrawExchangeId = $response->id;
        assertEquals('GCMMCKP2OJXLBZCANRHXSGMMUOGJQKNCHH7HQZ4G3ZFLAIBZY5ODJYO6', $response->accountId);
        assertEquals('id', $response->memoType);
        $withdrawMemo = $response->memo;
        assertNotNull($withdrawMemo);

        $request = new AnchorTransactionRequest();
        $request->jwt = $jwtToken;
        $request->id = $withdrawExchangeId;
        $response = $transferService->transaction($request);
        $tx = $response->transaction;
        assertNotNull($tx);

        assertEquals($withdrawExchangeId, $tx->id);
        assertEquals('withdraw-exchange', $tx->kind);
        assertEquals(Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE, $tx->status);
        assertEquals(100, floatval($tx->amountIn));
        assertEquals($withdrawMemo, $tx->withdrawMemo);
        assertEquals('id', $tx->withdrawMemoType);
        assertEquals($usdFiat->getStringRepresentation(), $tx->amountOutAsset);
        assertStringContainsString('JPYC', $tx->amountInAsset);
        $feeDetails = $tx->feeDetails;
        assertNotNull($feeDetails);
        assertEquals('1', $feeDetails->total);
        assertStringContainsString('JPYC', $feeDetails->asset);
        assertCount(1, $feeDetails->details);
        $detail = $feeDetails->details[0];
        assertEquals('Service fee', $detail->name);
        assertEquals('1', $detail->amount);
        assertEquals('GCMMCKP2OJXLBZCANRHXSGMMUOGJQKNCHH7HQZ4G3ZFLAIBZY5ODJYO6', $tx->withdrawAnchorAccount);

    }

    public function testDepositAndWithdrawExchangeWithQuotes() {

        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $transferService = $this->getTransferServerService();
        $quotesService = $this->getQuotesService();
        $usdFiat = IdentificationFormatAsset::fromString('iso4217:USD');
        $jpyc = IdentificationFormatAsset::fromString('stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U');
        $request = new SEP38PostQuoteRequest(
            context: 'sep6',
            sellAsset: $usdFiat->getStringRepresentation(),
            buyAsset: $jpyc->getStringRepresentation(),
            sellAmount: "100",
        );

        $quote = $quotesService->postQuote($request, $jwtToken);

        $request = new DepositExchangeRequest(
            destinationAsset: "JPYC",
            sourceAsset: $usdFiat->getStringRepresentation(),
            amount: "100",
            account: $this->userAccountId,
            quoteId: $quote->id,
            type: 'bank_account',
            jwt: $jwtToken,
        );

        $response = $transferService->depositExchange($request);
        assertNotNull($response->id);
        $depositExchangeId = $response->id;
        assertEquals('Check the transaction for more information about how to deposit.', $response->how);

        $request = new AnchorTransactionRequest();
        $request->jwt = $jwtToken;
        $request->id = $depositExchangeId;
        $response = $transferService->transaction($request);
        $tx = $response->transaction;
        assertNotNull($tx);

        assertEquals($depositExchangeId, $tx->id);
        assertEquals($quote->id, $tx->quoteId);
        assertEquals('deposit-exchange', $tx->kind);
        assertEquals(Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE, $tx->status);
        assertEquals(0, floatval($tx->amountIn));
        assertEquals($usdFiat->getStringRepresentation(), $tx->amountInAsset);
        assertStringContainsString('JPYC', $tx->amountOutAsset);

        $txFeeDetails = $tx->feeDetails;
        $quoteFeeDetails = $quote->fee;
        assertNotNull($txFeeDetails);
        assertNotNull($quoteFeeDetails);
        assertEquals($quoteFeeDetails->total, $txFeeDetails->total);
        assertEquals($quoteFeeDetails->asset, $txFeeDetails->asset);

        assertNotNull($tx->instructions);
        assertCount(2, $tx->instructions);
        $bankAccount = $tx->instructions['bank_number'];
        assertEquals('121122676', $bankAccount->value);
        assertEquals('Fake bank number', $bankAccount->description);

        $request = new SEP38PostQuoteRequest(
            context: 'sep6',
            sellAsset: $jpyc->getStringRepresentation(),
            buyAsset: $usdFiat->getStringRepresentation(),
            sellAmount: "100",
        );

        $quote = $quotesService->postQuote($request, $jwtToken);
        $request = new WithdrawExchangeRequest(
            sourceAsset: "JPYC",
            destinationAsset: $usdFiat->getStringRepresentation(),
            amount: "100.0",
            type: "bank_account",
            quoteId: $quote->id,
            account: $this->userAccountId,
            jwt: $jwtToken,
        );
        $response = $transferService->withdrawExchange($request);
        assertNotNull($response->id);
        $withdrawExchangeId = $response->id;
        assertEquals('GCMMCKP2OJXLBZCANRHXSGMMUOGJQKNCHH7HQZ4G3ZFLAIBZY5ODJYO6', $response->accountId);
        assertEquals('id', $response->memoType);
        $withdrawMemo = $response->memo;
        assertNotNull($withdrawMemo);

        $request = new AnchorTransactionRequest();
        $request->jwt = $jwtToken;
        $request->id = $withdrawExchangeId;
        $response = $transferService->transaction($request);
        $tx = $response->transaction;
        assertNotNull($tx);

        assertEquals($quote->id, $tx->quoteId);
        assertEquals($withdrawExchangeId, $tx->id);
        assertEquals('withdraw-exchange', $tx->kind);
        assertEquals(Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE, $tx->status);
        assertEquals(100, floatval($tx->amountIn));
        assertEquals($withdrawMemo, $tx->withdrawMemo);
        assertEquals('id', $tx->withdrawMemoType);
        assertEquals($usdFiat->getStringRepresentation(), $tx->amountOutAsset);
        assertStringContainsString('JPYC', $tx->amountInAsset);
        $txFeeDetails = $tx->feeDetails;
        $quoteFeeDetails = $quote->fee;
        assertNotNull($txFeeDetails);
        assertNotNull($quoteFeeDetails);
        assertEquals($quoteFeeDetails->total, $txFeeDetails->total);
        assertEquals($quoteFeeDetails->asset, $txFeeDetails->asset);

        $txHistoryRequest = new AnchorTransactionsRequest(assetCode: 'JPYC', account: $this->userAccountId );
        $txHistoryRequest->jwt = $jwtToken;
        $txsResponse = $transferService->transactions($txHistoryRequest);
        $transactions = $txsResponse->transactions;
        assertNotNull($transactions);
        assertGreaterThan(0, count($transactions));
    }

    private function getTransferServerService() : TransferServerService {
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        $stellarToml = StellarToml::fromDomain($this->domain, $client);
        $address = $stellarToml->getGeneralInformation()->transferServer;
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        return new TransferServerService(serviceAddress: $address, httpClient: $client);
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
        assertNotNull($generalInformation);
        assertNotNull($generalInformation->kYCServer);
        assertNotNull($generalInformation->webAuthEndpoint);

        $webAuth = WebAuth::fromDomain($this->domain, network: Network::testnet(), httpClient: $client);
        $jwtToken = $webAuth->jwtToken($keyPair->getAccountId(), [$keyPair]);

        assertNotNull($jwtToken);
        return $jwtToken;
    }
}


