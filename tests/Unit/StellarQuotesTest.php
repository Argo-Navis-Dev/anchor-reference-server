<?php

namespace Tests\Unit;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\Quote\QuoteService;
use Soneso\StellarSDK\SEP\Quote\SEP38PostQuoteRequest;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use function PHPUnit\Framework\assertNotNull;

class StellarQuotesTest extends TestCase
{

    private string $domain = 'localhost:5173';
    private string $usdcAsset = 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
    private string $jpycAsset = 'stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U';
    private string $usdAsset = 'iso4217:USD';
    private string $nativeAsset = 'stellar:native';


    public function testGetInfo()
    {
        $quotesService = $this->getQuotesService();
        $response = $quotesService->info();
        self::assertCount(4, $response->assets);
        $usdc = null;
        $jpyc = null;
        $usd = null;
        $native = null;
        foreach ($response->assets as $asset) {
            if ($asset->asset === $this->usdcAsset) {
                $usdc = $asset;
            } elseif ($asset->asset === $this->jpycAsset) {
                $jpyc = $asset;
            } elseif ($asset->asset === $this->usdAsset) {
                $usd = $asset;
            } elseif ($asset->asset === $this->nativeAsset) {
                $native = $asset;
            }
        }

        self::assertNotNull($usdc);
        self::assertNotNull($jpyc);
        self::assertNotNull($usd);
        self::assertNotNull($native);

        self::assertNotNull($usd->sellDeliveryMethods);
        self::assertNotNull($usd->buyDeliveryMethods);
        self::assertNotNull($usd->countryCodes);

        self::assertContains('USA', $usd->countryCodes);
        self::assertCount(1, $usd->sellDeliveryMethods);
        self::assertCount(1, $usd->buyDeliveryMethods);
        self::assertEquals('WIRE', $usd->sellDeliveryMethods[0]->name);
        self::assertEquals(
            "Send USD directly to the Anchor's bank account.",
            $usd->sellDeliveryMethods[0]->description,
        );
        self::assertEquals('WIRE', $usd->buyDeliveryMethods[0]->name);
        self::assertEquals(
            "Have USD sent directly to your bank account.",
            $usd->buyDeliveryMethods[0]->description,
        );
    }
    public function testGetPrices()
    {

        $quotesService = $this->getQuotesService();
        $response = $quotesService->prices(sellAsset: $this->usdAsset, sellAmount: 10);
        self::assertCount(2, $response->buyAssets);

        $usdc = null;
        $jpyc = null;
        foreach ($response->buyAssets as $asset) {
            if ($asset->asset === $this->usdcAsset) {
                $usdc = $asset;
            } elseif ($asset->asset === $this->jpycAsset) {
                $jpyc = $asset;
            }
        }

        self::assertNotNull($usdc);
        self::assertNotNull($jpyc);

        self::assertEquals("0.0066666666666667", $jpyc->price);
        self::assertEquals(2, $jpyc->decimals);

        self::assertEquals("1.010101010101", $usdc->price);
        self::assertEquals(2, $usdc->decimals);
    }

    public function testGetPrice()
    {
        $quotesService = $this->getQuotesService();
        $response = $quotesService->price(
            context: 'sep6',
            sellAsset: $this->usdAsset,
            buyAsset: $this->usdcAsset,
            sellAmount: "1000",
        );

        self::assertEquals("1.010101010101", $response->totalPrice);
        self::assertEquals("1", $response->price);
        self::assertEquals("1000", $response->sellAmount);
        self::assertEquals("990", $response->buyAmount);
        assertNotNull($response->fee);
        self::assertEquals("10", $response->fee->total);
        self::assertEquals($this->usdAsset, $response->fee->asset);
    }

    public function testPostQuoteAndGetById()
    {
        // create a new stellar account
        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();

        // request jwt token via sep-10
        $jwtToken = $this->getJwtToken($userKeyPair);
        $quotesService = $this->getQuotesService();

        $request = new SEP38PostQuoteRequest(
            context: 'sep6',
            sellAsset: $this->usdAsset,
            buyAsset: $this->usdcAsset,
            sellAmount: "1000",
        );

        $quote = $quotesService->postQuote($request, $jwtToken);
        self::assertEquals("1.010101010101", $quote->totalPrice);
        self::assertEquals("1", $quote->price);
        self::assertEquals($this->usdAsset, $quote->sellAsset);
        self::assertEquals("1000", $quote->sellAmount);
        self::assertEquals($this->usdcAsset, $quote->buyAsset);
        self::assertEquals("990", $quote->buyAmount);

        assertNotNull($quote->fee);
        self::assertEquals("10", $quote->fee->total);
        self::assertEquals($this->usdAsset, $quote->fee->asset);

        $quoteById = $quotesService->getQuote($quote->id, $jwtToken);

        self::assertEquals($quote->id, $quoteById->id);
        self::assertEquals("1.010101010101", $quoteById->totalPrice);
        self::assertEquals("1", $quoteById->price);
        self::assertEquals($this->usdAsset, $quoteById->sellAsset);
        self::assertEquals("1000", $quoteById->sellAmount);
        self::assertEquals($this->usdcAsset, $quoteById->buyAsset);
        self::assertEquals("990", $quoteById->buyAmount);

        assertNotNull($quoteById->fee);
        self::assertEquals("10", $quoteById->fee->total);
        self::assertEquals($this->usdAsset, $quoteById->fee->asset);
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
