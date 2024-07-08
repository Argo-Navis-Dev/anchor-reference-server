<?php

namespace Tests\Unit;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use phpseclib3\Math\BigInteger;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Account;
use Soneso\StellarSDK\AccountMergeOperationBuilder;
use Soneso\StellarSDK\Asset;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostActionDone;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionActionRequired;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionPending;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRejected;
use Soneso\StellarSDK\Transaction;
use Soneso\StellarSDK\AccountFlag;
use Soneso\StellarSDK\ChangeTrustOperationBuilder;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\PaymentOperationBuilder;
use Soneso\StellarSDK\Responses\Account\AccountResponse;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAsset;
use Soneso\StellarSDK\SEP\RegulatedAssets\RegulatedAssetsService;
use Soneso\StellarSDK\SEP\RegulatedAssets\SEP08PostTransactionRevised;
use Soneso\StellarSDK\SetOptionsOperationBuilder;
use Soneso\StellarSDK\StellarSDK;
use Soneso\StellarSDK\TransactionBuilder;
use Soneso\StellarSDK\Util\FriendBot;
use Throwable;
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertFalse;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

class StellarRegulatedAssetsTest extends TestCase
{

    private string $domain = 'localhost:5173';

    private string $senderSeed = 'SD2FMOQET3BRSFCNRFEV4WUCLRJOLCULMHEVULIOINXJ6HTQAMDATLER';
    private string $senderAccountId = "GDEWF77LQ54ILG72I2GTKABLMXUR6XFV3P4AMAVU4P7YKVVAKNUMADEI";

    private string $destinationSeed = 'SAINOOMNTTOMMLYPYZFJVTJA2M5REM6HWDU63TBJIYOS4NLOEX4UT32X';
    private string $destinationAccountId = "GBRCHDGNRUJQGLCXBRS7FPIEJXPZZZZW7VA2CHSVNKA3JFLFR2K76OFQ";

    private string $issuerSeed = 'SCVIZ3YQAMN2DNKEMVTCNYMHGZKZASRDKJSP6CRJVKB2UHHHEGM6DXNQ';
    private string $issuerAccountId = "GB6CPVUXGWPE33XY7CHUKKL5VOGIR6ADBVQQYZSWJ3Y2CQDUJKICSTAR";

    private StellarSDK $sdk;
    private Network $network;
    private RegulatedAssetsService $service;
    private RegulatedAsset $regulatedAsset;

    private string $statusPending = "pending";
    private string $statusApproved = "approved";
    private string $statusRejected = "rejected";

    private string $kycStatusUrl = 'https://localhost:5173/sep08/kyc-status/';
    private string $friendbotUrl = 'https://localhost:5173/sep08/friendbot?addr=';
    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);

        // init sdk & network
        $this->sdk = StellarSDK::getTestNetInstance();
        $this->network = Network::testnet();

        // fund accounts if needed
        $this->fundAccountIfNeeded($this->senderAccountId);
        $this->fundAccountIfNeeded($this->destinationAccountId);
        $this->fundAccountIfNeeded($this->issuerAccountId);

        // fetch regulated asset
        $this->service = $this->getRegulatedAssetsService();
        self::assertCount(1, $this->service->regulatedAssets);
        $this->regulatedAsset = $this->service->regulatedAssets[0];
        assertEquals($this->issuerAccountId, $this->regulatedAsset->getIssuer());

        // set auth required and auth revocable flags to issuer if not already set (see sep-08)
        $issuerAccount = $this->requestAccount($this->issuerAccountId);
        if (!$issuerAccount->getFlags()->isAuthRequired()  || ! $issuerAccount->getFlags()->isAuthRevocable()) {
            $txBuilder = new TransactionBuilder($issuerAccount);

            $setFlagsOp = (new SetOptionsOperationBuilder())
                ->setSetFlags(AccountFlag::AUTH_REQUIRED_FLAG | AccountFlag::AUTH_REVOCABLE_FLAG)
                ->build();
            $txBuilder->addOperation($setFlagsOp);
            $tx = $txBuilder->build();
            $tx->sign(KeyPair::fromSeed($this->issuerSeed), $this->network);
            try {
                $txResponse = $this->sdk->submitTransaction($tx);
                assertTrue($txResponse->isSuccessful());
            } catch (HorizonRequestException $e) {
                self::fail('could not set issuer flags ' . $e->getMessage());
            }
        }

        // sender must trust the regulated asset to be able to receive it.
        $senderAccount = $this->requestAccount($this->senderAccountId);
        $senderRegulatedAssetBalanceAmount = null;
        foreach ($senderAccount->getBalances()->toArray() as $balance) {
            if($balance->getAssetCode() === $this->regulatedAsset->getCode() &&
                $balance->getAssetIssuer() === $this->regulatedAsset->getIssuer()) {
                $senderRegulatedAssetBalanceAmount = $balance->getBalance();
                break;
            }
        }
        if ($senderRegulatedAssetBalanceAmount === null) {
            $txBuilder = new TransactionBuilder($senderAccount);
            $changeTrustOp = (new ChangeTrustOperationBuilder($this->regulatedAsset))->build();
            $txBuilder->addOperation($changeTrustOp);
            $tx = $txBuilder->build();
            $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);
            try {
                $txResponse = $this->sdk->submitTransaction($tx);
                assertTrue($txResponse->isSuccessful());
            } catch (HorizonRequestException $e) {
                self::fail('could not change sender trust ' . $e->getMessage());
            }
            $senderRegulatedAssetBalanceAmount = '0';
        }

        // send some regulated asset from the issuer to the sender account if needed.
        // so that it can later be used for testing a payment from the seder
        // account to the destination account.
        if(new BigInteger($senderRegulatedAssetBalanceAmount) < new BigInteger(20)) {
            if (!$this->friendbot($this->senderAccountId)) {
                self::fail('could not fund sender with regulated asset.');
            }
            // check if received.
            $senderAccount = $this->requestAccount($this->senderAccountId);
            $found = false;
            foreach ($senderAccount->getBalances() as $balance) {
                if ($balance->getAssetCode() === $this->regulatedAsset->getCode() &&
                    $balance->getAssetIssuer() === $this->regulatedAsset->getIssuer() &&
                    new BigInteger($balance->getBalance()) >= new BigInteger(100)) {
                    $found = true;
                    break;
                }
            }
            assertTrue($found);
        }

        // the destination account must also trust the regulated asset, so that it can receive it.
        $destinationAccount = $this->requestAccount($this->destinationAccountId);
        $trustlineExists = false;
        foreach ($senderAccount->getBalances()->toArray() as $balance) {
            if($balance->getAssetCode() === $this->regulatedAsset->getCode() &&
                $balance->getAssetIssuer() === $this->regulatedAsset->getIssuer()) {
                $trustlineExists = true;
                break;
            }
        }
        if (!$trustlineExists) {
            $txBuilder = new TransactionBuilder($destinationAccount);
            $changeTrustOp = (new ChangeTrustOperationBuilder($this->regulatedAsset))->build();
            $txBuilder->addOperation($changeTrustOp);
            $tx = $txBuilder->build();
            $tx->sign(KeyPair::fromSeed($this->destinationSeed), $this->network);
            try {
                $txResponse = $this->sdk->submitTransaction($tx);
                assertTrue($txResponse->isSuccessful());
            } catch (HorizonRequestException $e) {
                self::fail('could not change destination trust ' . $e->getMessage());
            }
        }
    }

    public function testSep08()
    {
        // first we try to send without approval, this should fail.
        $senderAccount = $this->requestAccount($this->senderAccountId);
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $txResponse = $this->sdk->submitTransaction($tx);
            assertFalse($txResponse->isSuccessful());
        } catch (HorizonRequestException $e) {
            $opResultCodes = $e->getHorizonErrorResponse()?->getExtras()?->getResultCodesOperation();
            assertNotNull($opResultCodes);
            assertContains('op_not_authorized', $opResultCodes);
        }

        // now let's approve it
        $senderAccount = $this->requestAccount($this->senderAccountId);
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);
        $service = $this->service;
        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionRevised);
        assertEquals('Authorization and deauthorization operations were added.', $response->message);

        // okay, now let's see if we can send the transaction to stellar
        try {
            $revisedTransaction = Transaction::fromEnvelopeBase64XdrString($response->tx);
            // since it was revised, we need to sign it again
            $revisedTransaction->sign(KeyPair::fromSeed($this->senderSeed), $this->network);
            $txResponse = $this->sdk->submitTransaction($revisedTransaction);
            assertTrue($txResponse->isSuccessful());
        } catch (HorizonRequestException $e) {
            self::fail('could not send approved payment ');
        }

        //**** TEST KYC ***//

        // clear kyc data for testing
        $this->deleteKycAccount($this->senderAccountId);

        // amount more than threshold (5)
        $senderAccount = $this->requestAccount($this->senderAccountId);
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '10'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        $service = $this->service;
        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionActionRequired);
        assertEquals('Please provide your email address.', $response->message);
        assertEquals($this->kycStatusUrl . $this->senderAccountId, $response->actionUrl);
        assertEquals('POST', $response->actionMethod);
        assertContains('email_address', $response->actionFields);

        // check kyc status
        $status = $this->getKycStatus($this->senderAccountId);
        assertEquals($this->statusPending, $status);

        // send email address
        try {
            $response = $service->postAction(
                $this->kycStatusUrl . $this->senderAccountId,
                ['email_address' => 'friend@anchor-sdk.com'],
            );
            assert($response instanceof SEP08PostActionDone);
        } catch (Throwable $e) {
            self::fail('could not post kyc data ' . $e->getMessage());
        }

        // check kyc status
        $status = $this->getKycStatus($this->senderAccountId);
        assertEquals($this->statusApproved, $status);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionRevised);
        assertEquals('Authorization and deauthorization operations were added.', $response->message);

        // okay, now let's see if we can send the transaction to stellar
        try {
            $revisedTransaction = Transaction::fromEnvelopeBase64XdrString($response->tx);
            // since it was revised, we need to sign it again
            $revisedTransaction->sign(KeyPair::fromSeed($this->senderSeed), $this->network);
            $txResponse = $this->sdk->submitTransaction($revisedTransaction);
            assertTrue($txResponse->isSuccessful());
        } catch (HorizonRequestException $e) {
            self::fail('could not send approved payment ');
        }

        // clear kyc data for next test
        $this->deleteKycAccount($this->senderAccountId);

        $senderAccount = $this->requestAccount($this->senderAccountId);
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '10'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        // test rejected
        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionActionRequired);

        // check kyc status
        $status = $this->getKycStatus($this->senderAccountId);
        assertEquals($this->statusPending, $status);

        // send email address starting with "x" => will be rejected.
        try {
            $response = $service->postAction(
                $this->kycStatusUrl . $this->senderAccountId,
                ['email_address' => 'x_friend@anchor-sdk.com'],
            );
            assert($response instanceof SEP08PostActionDone);
        } catch (Throwable $e) {
            self::fail('could not post kyc data ' . $e->getMessage());
        }

        // check kyc status
        $status = $this->getKycStatus($this->senderAccountId);
        assertEquals($this->statusRejected, $status);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionRejected);


        // clear kyc data for testing
        $this->deleteKycAccount($this->senderAccountId);

        // test pending
        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionActionRequired);

        // check kyc status
        $status = $this->getKycStatus($this->senderAccountId);
        assertEquals($this->statusPending, $status);

        // send email address starting with "y" => will be pending.
        try {
            $response = $service->postAction(
                $this->kycStatusUrl . $this->senderAccountId,
                ['email_address' => 'y_friend@anchor-sdk.com'],
            );
            assert($response instanceof SEP08PostActionDone);
        } catch (Throwable $e) {
            self::fail('could not post kyc data ' . $e->getMessage());
        }

        // check kyc status
        $status = $this->getKycStatus($this->senderAccountId);
        assertEquals($this->statusPending, $status);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionPending);

        //**** TEST Validation Errors ***//

        // Error: Transaction source account can not be issuer account.
        $issuerAccount = $this->requestAccount($this->issuerAccountId);
        $txBuilder = new TransactionBuilder($issuerAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        $service = $this->service;

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }
        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals('Transaction source account can not be issuer account.', $response->error);

        // Error: "Please submit a transaction with exactly one operation of type payment."
        $senderAccount = $this->requestAccount($this->senderAccountId);
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals('Please submit a transaction with exactly one operation of type payment.', $response->error);

        // Error: There is an unauthorized operation in the provided transaction.
        $txBuilder = new TransactionBuilder($senderAccount);
        $otherOp = (new AccountMergeOperationBuilder($this->issuerAccountId))->build();
        $txBuilder->addOperation($otherOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals('There is an unauthorized operation in the provided transaction.', $response->error);

        // Error: Payment operation source account can not be issuer account.
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (
            new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3')
        )->setSourceAccount($this->issuerAccountId)->build();

        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals('Payment operation source account can not be issuer account.', $response->error);

        // Error: Can't transfer asset to its issuer.
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->issuerAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("Can't transfer asset to its issuer.", $response->error);

        // Error(1): The payment asset is not supported by this issuer.
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, Asset::native(), '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("The payment asset is not supported by this issuer.", $response->error);

        // Error(2): The payment asset is not supported by this issuer.
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (
            new PaymentOperationBuilder(
                    $this->destinationAccountId,
                    Asset::createFromCanonicalForm('BLUB:'.$this->issuerAccountId),
                    '3')
        )->build();

        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("The payment asset is not supported by this issuer.", $response->error);

        // Error(3): The payment asset is not supported by this issuer.
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (
        new PaymentOperationBuilder(
            $this->destinationAccountId,
            Asset::createFromCanonicalForm($this->regulatedAsset->getCode().':'.$this->destinationAccountId),
            '3')
        )->build();

        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("The payment asset is not supported by this issuer.", $response->error);

        // Error: Payment source account must be the same as the transaction source account.
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))
            ->setSourceAccount(KeyPair::random()->getAccountId())->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("Payment source account must be the same as the transaction source account.", $response->error);

        // Error: Transaction source account must exist on the Stellar network.
        $xSourceAccount = new Account(KeyPair::random()->getAccountId(), new BigInteger(1));
        $txBuilder = new TransactionBuilder($xSourceAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("Transaction source account must exist on the Stellar network.", $response->error);

        // Error Invalid transaction sequence number.
        $senderAccount = $this->requestAccount($this->senderAccountId);
        $senderAccount->incrementSequenceNumber();
        $txBuilder = new TransactionBuilder($senderAccount);
        $paymentOp = (new PaymentOperationBuilder($this->destinationAccountId, $this->regulatedAsset, '3'))->build();
        $txBuilder->addOperation($paymentOp);
        $tx = $txBuilder->build();
        $tx->sign(KeyPair::fromSeed($this->senderSeed), $this->network);

        try {
            $response = $service->postTransaction($tx->toEnvelopeXdrBase64(), $this->regulatedAsset->approvalServer);
        } catch (Throwable $e) {
            self::fail('could not approve ' . $e->getMessage());
        }

        assert($response instanceof SEP08PostTransactionRejected);
        assertEquals("Invalid transaction sequence number.", $response->error);

    }

    private function getRegulatedAssetsService() : RegulatedAssetsService {
        try {
            $client = new Client([
                'verify' => false, // This disables SSL verification
            ]);
            return RegulatedAssetsService::fromDomain(domain: $this->domain, httpClient: $client);
        } catch (Throwable $e) {
            self::fail('error creating regulated assets service: ' . $e->getMessage());
        }
    }

    private function fundAccountIfNeeded(string $accountId): void
    {
        try {
            if (!$this->sdk->accountExists($accountId)) {
                FriendBot::fundTestAccount($accountId);
            }
        } catch (Throwable $e) {
            self::fail('error funding account: ' . $e->getMessage());
        }
    }

    private function requestAccount(string $accountId): AccountResponse {
        try {
            return $this->sdk->requestAccount($accountId);
        } catch (Throwable $e) {
            self::fail('could not request account ' . $e->getMessage());
        }
    }

    private function getKycStatus(string $accountId): string {

        $url = $this->kycStatusUrl . $accountId;
        $client = new Client(['verify' => false, 'http_errors' => false]);
        try {
            $response = $client->get($url);
            if ($response->getStatusCode() === 200) {
                $decoded = json_decode($response->getBody()->getContents(), true);
                return $decoded['status'];
            } else if ($response->getStatusCode() === 404) {
                $decoded = json_decode($response->getBody()->getContents(), true);
                return $decoded['message'];
            } else {
                return strval($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $e->getMessage();
        }
    }

    private function deleteKycAccount(string $accountId): string {

        $url = $this->kycStatusUrl . $accountId;
        $client = new Client(['verify' => false, 'http_errors' => false]);
        try {
            $response = $client->delete($url);
            if ($response->getStatusCode() === 200 || $response->getStatusCode() === 404) {
                $decoded = json_decode($response->getBody()->getContents(), true);
                return $decoded['message'];
            } else {
                return strval($response->getStatusCode());
            }
        } catch (GuzzleException $e) {
            return $e->getMessage();
        }
    }

    private function friendbot(string $accountId): bool {

        $url = $this->friendbotUrl . $accountId;
        $client = new Client(['verify' => false, 'http_errors' => false]);
        try {
            $response = $client->get($url);
            if ($response->getStatusCode() === 200) {
                return true;
            }
        } catch (GuzzleException $e) {
            print($e->getMessage());
        }
        return false;
    }
}
