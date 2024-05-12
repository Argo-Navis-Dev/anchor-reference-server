<?php

namespace Tests\Unit;

use ArgoNavis\PhpAnchorSdk\shared\Sep31TransactionStatus;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Util\FriendBot;
use Soneso\StellarSDK\SEP\CrossBorderPayments\CrossBorderPaymentsService;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31ReceiveAssetInfo;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP12TypesInfo;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31PostTransactionsRequest;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31FeeDetails;
use Soneso\StellarSDK\SEP\CrossBorderPayments\SEP31FeeDetailsDetails;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Soneso\StellarSDK\Network;

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

class StellarCrossBorderTest extends TestCase
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
        $this->markTestSkipped("Performance issues");
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $crossBorderPaymentsService = $this->getCrossBorderPaymentService();
        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $response = $crossBorderPaymentsService->info($jwtToken);
        assertNotNull($response);
        assertCount(2, $response->receiveAssets);

        $receiveAssetUsdc = $response->receiveAssets["USDC"];
        assertNotNull($receiveAssetUsdc);
        assertTrue($receiveAssetUsdc instanceof SEP31ReceiveAssetInfo);
        assertEquals("1", $receiveAssetUsdc->minAmount);
        assertEquals("1000", $receiveAssetUsdc->maxAmount);
        assertTrue($receiveAssetUsdc->quotesSupported);
        assertFalse($receiveAssetUsdc->quotesRequired);
        assertNotNull($receiveAssetUsdc->sep12Info);
        $sep12Info = $receiveAssetUsdc->sep12Info;
        assertTrue($sep12Info instanceof SEP12TypesInfo);
        $senderTypes = $sep12Info->senderTypes;
        assertCount(3, $senderTypes);
        assertNotNull($senderTypes["sep31-sender"]);
        assertEquals("U.S. citizens limited to sending payments of less than $10,000 in value",
            $senderTypes["sep31-sender"]);
        assertNotNull($senderTypes["sep31-large-sender"]);
            assertEquals("U.S. citizens that do not have sending limits",
                $senderTypes["sep31-large-sender"]);

        assertNotNull($senderTypes["sep31-foreign-sender"]);
            assertEquals("non-U.S. citizens sending payments of less than $10,000 in value",
                $senderTypes["sep31-foreign-sender"]);

        $receiverTypes = $sep12Info->receiverTypes;
        assertCount(2, $receiverTypes);
        assertNotNull($receiverTypes["sep31-receiver"]);
        assertEquals("U.S. citizens receiving USD",
            $receiverTypes["sep31-receiver"]);
        assertNotNull($receiverTypes["sep31-foreign-receiver"]);
            assertEquals("non-U.S. citizens receiving USD",
                $receiverTypes["sep31-foreign-receiver"]);

        $receiveAssetJpyc = $response->receiveAssets["JPYC"];
        assertNotNull($receiveAssetJpyc);
        assertTrue($receiveAssetJpyc instanceof SEP31ReceiveAssetInfo);
        assertEquals("1", $receiveAssetJpyc->minAmount);
        assertEquals("1000000", $receiveAssetJpyc->maxAmount);
        assertTrue($receiveAssetJpyc->quotesSupported);
        assertFalse($receiveAssetJpyc->quotesRequired);
        $sep12Info = $receiveAssetJpyc->sep12Info;
        assertTrue($sep12Info instanceof SEP12TypesInfo);
        $senderTypes = $sep12Info->senderTypes;
        assertCount(3, $senderTypes);
        assertNotNull($senderTypes["sep31-sender"]);
        assertEquals("U.S. citizens limited to sending payments of less than $10,000 in value",
            $senderTypes["sep31-sender"]);
        assertNotNull($senderTypes["sep31-large-sender"]);
            assertEquals("U.S. citizens that do not have sending limits",
                $senderTypes["sep31-large-sender"]);
        assertNotNull($senderTypes["sep31-foreign-sender"]);
            assertEquals("non-U.S. citizens sending payments of less than $10,000 in value",
                $senderTypes["sep31-foreign-sender"]);

        $receiverTypes = $sep12Info->receiverTypes;
        assertCount(2, $receiverTypes);
        assertNotNull($receiverTypes["sep31-receiver"]);
        assertEquals("U.S. citizens receiving USD",
            $receiverTypes["sep31-receiver"]);
        assertNotNull($receiverTypes["sep31-foreign-receiver"]);
            assertEquals("non-U.S. citizens receiving USD",
                $receiverTypes["sep31-foreign-receiver"]);
    }

    public function testPostTransactions()
    {
        $this->markTestSkipped("Performance issues");
        $this->markTestSkipped("Performance issues");
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $crossBorderPaymentsService = $this->getCrossBorderPaymentService();
        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);

        $transactionsRequest = new SEP31PostTransactionsRequest(
            amount: 100.0,
            assetCode: "USDC",
            destinationAsset: "iso4217:USD",
            senderId: "9bff23f0-d1ff-442a-b366-3143cbc28bf5",
            receiverId: "9bff0aee-4290-402a-9003-7abd8ae85ac1",
        );
        $response = $crossBorderPaymentsService->postTransactions($transactionsRequest, $jwtToken);
        assertNotNull($response);
        assertNotNull($response->id);
        assertEquals("GAKRN7SCC7KVT52XLMOFFWOOM4LTI2TQALFKKJ6NKU3XWPNCLD5CFRY2", $response->stellarAccountId);
        assertEquals('id', $response->stellarMemoType);
        assertNotNull($response->stellarMemo);
    }

    public function testPostTransactionsAndGetById()
    {
        $this->markTestSkipped("Performance issues");
        $usdcAsset = 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
        $usdAsset = 'iso4217:USD';

        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $crossBorderPaymentsService = $this->getCrossBorderPaymentService();
        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);

        $transactionsRequest = new SEP31PostTransactionsRequest(
            amount: 100.0,
            assetCode: "USDC",
            destinationAsset: $usdAsset,
            senderId: "9bff23f0-d1ff-442a-b366-3143cbc28bf5",
            receiverId: "9bff0aee-4290-402a-9003-7abd8ae85ac1",
        );
        $postTransactionResponse = $crossBorderPaymentsService->postTransactions($transactionsRequest, $jwtToken);
        assertNotNull($postTransactionResponse);
        assertNotNull($postTransactionResponse->id);

        $getTransactionResponse = $crossBorderPaymentsService->getTransaction(
            id: $postTransactionResponse->id,
            jwt: $jwtToken
        );
        assertNotNull($getTransactionResponse);
        assertEquals($postTransactionResponse->id, $getTransactionResponse->id);
        assertEquals($postTransactionResponse->stellarAccountId, $getTransactionResponse->stellarAccountId);
        assertEquals(Sep31TransactionStatus::PENDING_RECEIVER, $getTransactionResponse->status);
        assertEquals(100.0, $getTransactionResponse->amountIn);
        assertEquals($usdcAsset, $getTransactionResponse->amountInAsset);
        assertEquals($usdAsset, $getTransactionResponse->amountOutAsset);
        assertEquals("GAKRN7SCC7KVT52XLMOFFWOOM4LTI2TQALFKKJ6NKU3XWPNCLD5CFRY2", $getTransactionResponse->stellarAccountId);
        assertEquals("id", $getTransactionResponse->stellarMemoType);
        assertNotNull($getTransactionResponse->stellarMemo);
        assertNotNull($getTransactionResponse->startedAt);

        assertNotNull($getTransactionResponse->feeDetails);
        $feeDetails = $getTransactionResponse->feeDetails;
        assertTrue($feeDetails instanceof SEP31FeeDetails);
        assertEquals(0.1, $feeDetails->total);
        assertEquals($usdAsset, $feeDetails->asset);

        assertNotNull($feeDetails->details);
        assertCount(1, $feeDetails->details);

        $feeDetail = $feeDetails->details[0];
        assertTrue($feeDetail instanceof SEP31FeeDetailsDetails);
        assertEquals("Service fee", $feeDetail->name);
        assertEquals("0.1", $feeDetail->amount);

    }

    public function testPutTransactionCallback()
    {
        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $crossBorderPaymentsService = $this->getCrossBorderPaymentService();
        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);

        $usdcAsset = 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
        $usdAsset = 'iso4217:USD';

        $jwtToken = $this->getJwtToken($this->userKeyPair);
        $crossBorderPaymentsService = $this->getCrossBorderPaymentService();
        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($this->userKeyPair);

        $transactionsRequest = new SEP31PostTransactionsRequest(
            amount: 100.0,
            assetCode: "USDC",
            destinationAsset: $usdAsset,
            senderId: "9bff23f0-d1ff-442a-b366-3143cbc28bf5",
            receiverId: "9bff0aee-4290-402a-9003-7abd8ae85ac1",
        );
        $postTransactionResponse = $crossBorderPaymentsService->postTransactions($transactionsRequest, $jwtToken);
        assertNotNull($postTransactionResponse);
        assertNotNull($postTransactionResponse->id);
        print("Transaction ID: " . $postTransactionResponse->id . "\n");
        /*$crossBorderPaymentsService->putTransactionCallback(
            id: $postTransactionResponse->id,
            callbackUrl: 'https://test.com/sep31/transactions/callback',
            jwt: $jwtToken
        );*/

    }

    private function getCrossBorderPaymentService(): CrossBorderPaymentsService
    {
        $client = new Client([
            'verify' => false, // This disables SSL verification
        ]);
        $stellarToml = StellarToml::fromDomain(domain: $this->domain, httpClient: $client);
        $address = $stellarToml->getGeneralInformation()->directPaymentServer;

        return new CrossBorderPaymentsService(serviceAddress: $address, httpClient: $client);
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