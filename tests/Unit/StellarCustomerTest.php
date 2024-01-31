<?php

namespace Tests\Unit;

use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Soneso\StellarSDK\Crypto\KeyPair;
use Soneso\StellarSDK\Network;
use Soneso\StellarSDK\SEP\KYCService\GetCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\KYCService;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerInfoRequest;
use Soneso\StellarSDK\SEP\KYCService\PutCustomerVerificationRequest;
use Soneso\StellarSDK\SEP\StandardKYCFields\NaturalPersonKYCFields;
use Soneso\StellarSDK\SEP\StandardKYCFields\StandardKYCFields;
use Soneso\StellarSDK\SEP\Toml\StellarToml;
use Soneso\StellarSDK\SEP\WebAuth\WebAuth;
use Throwable;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotNull;
use function PHPUnit\Framework\assertTrue;

class StellarCustomerTest extends TestCase
{
    // See: https://dev.to/robertobutti/laravel-artisan-serve-and-https-cb0
    private string $domain = 'localhost:5173';

    public function test_get_unknown_customer(): void
    {
        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();

        $jwtToken = $this->getJwtToken($userKeyPair);
        $kycService = $this->getKycService();

        $request = new GetCustomerInfoRequest();
        $request->account = $userAccountId;
        $request->jwt = $jwtToken;

        $response = $kycService->getCustomerInfo($request);

        self::assertEquals(CustomerStatus::NEEDS_INFO, $response->getStatus());
        $fields = $response->getFields();
        self::assertGreaterThan(0, count($fields));
        self::assertNull($response->getProvidedFields());
    }

    public function test_known_customer(): void
    {
        $userKeyPair = KeyPair::random();
        $userAccountId = $userKeyPair->getAccountId();
        $jwtToken = $this->getJwtToken($userKeyPair);
        $kycService = $this->getKycService();

        // add new customer
        $request = new PutCustomerInfoRequest();
        $request->account = $userAccountId;
        $request->jwt = $jwtToken;
        $naturalPersonFields = new NaturalPersonKYCFields();
        $naturalPersonFields->firstName = "John";
        $naturalPersonFields->lastName = "Doe";
        $filePath = 'tests/files/id_front.png';
        if (str_ends_with(getcwd(), 'Unit')) {
            $filePath = '../files/id_front.png';
        }
        $naturalPersonFields->photoIdFront = file_get_contents($filePath, false);
        $naturalPersonFields->emailAddress = "c.rogobete@soneso.com";
        $kyc = new StandardKYCFields();
        $kyc->naturalPersonKYCFields = $naturalPersonFields;
        $request->KYCFields = $kyc;
        $response = $kycService->putCustomerInfo($request);
        self::assertNotNull($response->getId());
        $id = $response->getId();

        $getInfoRequest = new GetCustomerInfoRequest();
        $getInfoRequest->id = $id;
        $getInfoRequest->jwt = $jwtToken;
        $response = $kycService->getCustomerInfo($getInfoRequest);
        assertEquals(CustomerStatus::NEEDS_INFO, $response->getStatus());
        $fields = $response->getFields();
        assertNotNull($fields);
        $providedFields = $response->getProvidedFields();
        assertNotNull($providedFields);
        $emailField = $providedFields['email_address'];
        assertEquals(ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED, $emailField->getStatus());


        // update customer
        $naturalPersonFields = new NaturalPersonKYCFields();
        $naturalPersonFields->lastName = "Doe2";
        $naturalPersonFields->idNumber = "91283763";
        $naturalPersonFields->idType = "Passport";
        $kyc->naturalPersonKYCFields = $naturalPersonFields;
        $request->id = $id;
        $request->KYCFields = $kyc;
        $response = $kycService->putCustomerInfo($request);
        self::assertEquals($id, $response->getId());

        $getInfoRequest = new GetCustomerInfoRequest();
        $getInfoRequest->id = $id;
        $getInfoRequest->jwt = $jwtToken;
        $response = $kycService->getCustomerInfo($getInfoRequest);
        assertNotNull($response->getFields());
        //print_r($response->getFields());
        assertNotNull($response->getProvidedFields());
        assertEquals(CustomerStatus::PROCESSING, $response->getStatus());

        // verify email address
        $verificationRequest = new PutCustomerVerificationRequest();
        $verificationRequest->id = $id;
        $verificationRequest->jwt = $jwtToken;
        $fields = array();
        $fields['email_address_verification'] = '123456';
        $verificationRequest->verificationFields = $fields;

        // random code will not match
        $thrown = false;
        try {
            $kycService->putCustomerVerification($verificationRequest);
        } catch (Throwable) {
            // invalid code
            $thrown = true;
        }
        assertTrue($thrown);


        /*
        // to test this, you need to set a fixed code in Sep12Helper::sendVerificationCode()
        $response = $kycService->putCustomerVerification($verificationRequest);
        $providedFields = $response->getProvidedFields();
        assertNotNull($providedFields);
        $emailField = $providedFields['email_address'];
        assertEquals(ProvidedCustomerFieldStatus::ACCEPTED, $emailField->getStatus());
        */
        $kycService->deleteCustomer($userAccountId, $jwtToken);

        $request = new GetCustomerInfoRequest();
        $request->account = $userAccountId;
        $request->jwt = $jwtToken;

        $response = $kycService->getCustomerInfo($request);

        self::assertEquals(CustomerStatus::NEEDS_INFO, $response->getStatus());
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
