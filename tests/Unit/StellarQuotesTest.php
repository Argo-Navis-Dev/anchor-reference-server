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
use function PHPUnit\Framework\assertContains;
use function PHPUnit\Framework\assertCount;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use const E_ALL;
use function error_reporting;

class StellarQuotesTest extends TestCase
{

    private string $domain = 'localhost:5173';
    private string $usdcAsset = 'stellar:USDC:GDC4MJVYQBCQY6XYBZZBLGBNGFOGEFEZDRXTQ3LXFA3NEYYT6QQIJPA2';
    private string $jpycAsset = 'stellar:JPYC:GBDQ4I7EIIPAIEBGN4GOKTU7MGUCOOC37NYLNRBN76SSWOWFGLWTXW3U';
    private string $usdAsset = 'iso4217:USD';
    private string $nativeAsset = 'stellar:native';

    public function setUp(): void
    {
        // Turn on error reporting
        error_reporting(E_ALL);
    }

    public function testGetInfo()
    {
        $quotesService = $this->getQuotesService();
        $response = $quotesService->info();
        assertCount(4, $response->assets);
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

        assertNotNull($usdc);
        assertNotNull($jpyc);
        assertNotNull($usd);
        assertNotNull($native);

        assertNotNull($usd->sellDeliveryMethods);
        assertNotNull($usd->buyDeliveryMethods);
        assertNotNull($usd->countryCodes);

        assertContains('USA', $usd->countryCodes);
        assertCount(1, $usd->sellDeliveryMethods);
        assertCount(1, $usd->buyDeliveryMethods);
        assertEquals('WIRE', $usd->sellDeliveryMethods[0]->name);
        assertEquals(
            "Send USD directly to the Anchor's bank account.",
            $usd->sellDeliveryMethods[0]->description,
        );
        assertEquals('WIRE', $usd->buyDeliveryMethods[0]->name);
        assertEquals(
            "Have USD sent directly to your bank account.",
            $usd->buyDeliveryMethods[0]->description,
        );
    }
    public function testGetPrices()
    {

        $quotesService = $this->getQuotesService();
        $response = $quotesService->prices(sellAsset: $this->usdAsset, sellAmount: 10);
        assertCount(2, $response->buyAssets);

        $usdc = null;
        $jpyc = null;
        foreach ($response->buyAssets as $asset) {
            if ($asset->asset === $this->usdcAsset) {
                $usdc = $asset;
            } elseif ($asset->asset === $this->jpycAsset) {
                $jpyc = $asset;
            }
        }

        assertNotNull($usdc);
        assertNotNull($jpyc);

        assertEquals("0.0066666666666667", $jpyc->price);
        assertEquals(2, $jpyc->decimals);

        assertEquals("1.010101010101", $usdc->price);
        assertEquals(2, $usdc->decimals);
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

        assertEquals("1.010101010101", $response->totalPrice);
        assertEquals("1", $response->price);
        assertEquals("1000", $response->sellAmount);
        assertEquals("990", $response->buyAmount);
        assertNotNull($response->fee);
        assertEquals("10", $response->fee->total);
        assertEquals($this->usdAsset, $response->fee->asset);
    }

    public function testPostQuoteAndGetById()
    {
        // create a new stellar account
        $userKeyPair = KeyPair::random();

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
        assertEquals("1.010101010101", $quote->totalPrice);
        assertEquals("1", $quote->price);
        assertEquals($this->usdAsset, $quote->sellAsset);
        assertEquals("1000", $quote->sellAmount);
        assertEquals($this->usdcAsset, $quote->buyAsset);
        assertEquals("990", $quote->buyAmount);

        assertNotNull($quote->fee);
        assertEquals("10", $quote->fee->total);
        assertEquals($this->usdAsset, $quote->fee->asset);

        $quoteById = $quotesService->getQuote($quote->id, $jwtToken);

        assertEquals($quote->id, $quoteById->id);
        assertEquals("1.010101010101", $quoteById->totalPrice);
        assertEquals("1", $quoteById->price);
        assertEquals($this->usdAsset, $quoteById->sellAsset);
        assertEquals("1000", $quoteById->sellAmount);
        assertEquals($this->usdcAsset, $quoteById->buyAsset);
        assertEquals("990", $quoteById->buyAmount);

        assertNotNull($quoteById->fee);
        assertEquals("10", $quoteById->fee->total);
        assertEquals($this->usdAsset, $quoteById->fee->asset);
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
