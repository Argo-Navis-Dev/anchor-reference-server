<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Sep12FieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->insertNaturalPersonFields();
        $this->insertFinancialAccountFields();
        $this->insertOrganizationFields();
        $this->insertCardFields();
    }

    private function insertNaturalPersonFields(): void
    {
        DB::table('sep12_fields')->insert([
            'key' => 'last_name',
            'type' => 'string',
            'desc' => 'Family or last name',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'first_name',
            'type' => 'string',
            'desc' => 'Given or first name',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'additional_name',
            'type' => 'string',
            'desc' => 'Middle name or other additional name',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'address_country_code',
            'type' => 'string',
            'desc' => 'Country code for current address',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'state_or_province',
            'type' => 'string',
            'desc' => 'Name of state/province/region/prefecture',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'city',
            'type' => 'string',
            'desc' => 'Name of city/town',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'postal_code',
            'type' => 'string',
            'desc' => "Postal or other code identifying user's locale",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'address',
            'type' => 'string',
            'desc' => 'Entire address (country, state, postal code, street address, etc...) as a multi-line string',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'mobile_number',
            'type' => 'string',
            'desc' => "Mobile phone number with country code, in E.164 format unless specified differently on mobile_number_format field. It could be hashed in case mobile_number_format is defined as hash",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'mobile_number_format',
            'type' => 'string',
            'choices' => 'E.164, hash',
            'desc' => "Expected format of the mobile_number field. E.g.: E.164, hash, etc... In case this field is not specified, receiver should assume it's in E.164 format",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'email_address',
            'type' => 'string',
            'desc' => 'Email address',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'birth_date',
            'type' => 'date',
            'desc' => 'Date of birth, e.g. 1976-07-04',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'birth_place',
            'type' => 'string',
            'desc' => 'Place of birth (city, state, country; as on passport)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'birth_country_code',
            'type' => 'string',
            'desc' => 'ISO Code of country of birth',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'tax_id',
            'type' => 'string',
            'desc' => 'Tax identifier of user in their country (social security number in US)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'tax_id_name',
            'type' => 'string',
            'desc' => 'Name of the tax ID (SSN or ITIN in the US)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'occupation',
            'type' => 'number',
            'desc' => 'Occupation ISCO code',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'employer_name',
            'type' => 'string',
            'desc' => 'Name of employer',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'employer_address',
            'type' => 'string',
            'desc' => 'Address of employer',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'language_code',
            'type' => 'string',
            'desc' => 'Primary language',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_type',
            'type' => 'string',
            'choices' => "Passport, ID Card, Drivers Licence",
            'desc' => 'Passport, drivers_license, id_card, etc...',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_country_code',
            'type' => 'string',
            'desc' => 'Country issuing passport or photo ID as ISO 3166-1 alpha-3 code',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_issue_date',
            'type' => 'date',
            'desc' => 'ID issue date',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_expiration_date',
            'type' => 'date',
            'desc' => 'ID expiration date',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'id_number',
            'type' => 'string',
            'desc' => 'Passport or ID number',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'photo_id_front',
            'type' => 'binary',
            'desc' => "Image of front of user's photo ID or passport",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'photo_id_back',
            'type' => 'binary',
            'desc' => "Image of back of user's photo ID or passport",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'notary_approval_of_photo_id',
            'type' => 'binary',
            'desc' => "Image of notary's approval of photo ID or passport",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'ip_address',
            'type' => 'string',
            'desc' => "IP address of customer's computer",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'photo_proof_residence',
            'type' => 'binary',
            'desc' => "Image of a utility bill, bank statement or similar with the user's name and address",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'sex',
            'type' => 'string',
            'desc' => 'Male, female, or other',
            'choices' => 'Male, Female, Other',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'proof_of_income',
            'type' => 'binary',
            'desc' => "Image of user's proof of income document",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'proof_of_liveness',
            'type' => 'binary',
            'desc' => 'Video or image file of user as a liveness proof',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'referral_id',
            'type' => 'string',
            'desc' => "User's origin (such as an id in another application) or a referral code",
            'created_at' => now(),
        ]);
    }

    private  function insertFinancialAccountFields(): void
    {
        DB::table('sep12_fields')->insert([
            'key' => 'bank_name',
            'type' => 'string',
            'desc' => 'Name of the bank. May be necessary in regions that don\'t have a unified routing system.',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'bank_account_type',
            'type' => 'string',
            'desc' => 'checking or savings',
            'choices' => 'checking, savings',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'bank_account_number',
            'type' => 'string',
            'desc' => 'Number identifying bank account',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'bank_number',
            'type' => 'string',
            'desc' => 'Number identifying bank in national banking system (routing number in US)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'bank_phone_number',
            'type' => 'string',
            'desc' => 'Phone number with country code for bank',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'bank_branch_number',
            'type' => 'string',
            'desc' => 'Number identifying bank branch',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'external_transfer_memo',
            'type' => 'string',
            'desc' => 'A destination tag/memo used to identify a transaction',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'clabe_number',
            'type' => 'string',
            'desc' => 'Bank account number for Mexico',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'cbu_number',
            'type' => 'string',
            'desc' => 'Clave Bancaria Uniforme (CBU) or Clave Virtual Uniforme (CVU).',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'cbu_alias',
            'type' => 'string',
            'desc' => 'The alias for a Clave Bancaria Uniforme (CBU) or Clave Virtual Uniforme (CVU).',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'mobile_money_number',
            'type' => 'string',
            'desc' => 'Mobile phone number in E.164 format with which a mobile money account is associated. Note that this number may be distinct from the same customer\'s mobile_number.',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'mobile_money_provider',
            'type' => 'string',
            'desc' => 'Name of the mobile money service provider.',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'crypto_address',
            'type' => 'string',
            'desc' => 'Address for a cryptocurrency account',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'crypto_memo',
            'type' => 'string',
            'desc' => '(deprecated, use external_transfer_memo instead) A destination tag/memo used to identify a transaction',
            'created_at' => now(),
        ]);
    }

    private function insertOrganizationFields(): void
    {
        DB::table('sep12_fields')->insert([
            'key' => 'organization.name',
            'type' => 'string',
            'desc' => 'Full organization name as on the incorporation',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.VAT_number',
            'type' => 'string',
            'desc' => 'Organization VAT number',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.registration_number',
            'type' => 'string',
            'desc' => 'Organization registration number',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.registration_date',
            'type' => 'string',
            'desc' => 'Date the organization was registered',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.registered_address',
            'type' => 'string',
            'desc' => 'Organization registered address',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.number_of_shareholders',
            'type' => 'number',
            'desc' => 'Organization shareholder number',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.shareholder_name',
            'type' => 'string',
            'desc' => 'Can be an organization or a person',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.photo_incorporation_doc',
            'type' => 'binary',
            'desc' => 'Image of incorporation documents',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.photo_proof_address',
            'type' => 'binary',
            'desc' => "Image of a utility bill, bank statement with the organization's name and address",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.address_country_code',
            'type' => 'string',
            'desc' => 'Country code for current address',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.state_or_province',
            'type' => 'string',
            'desc' => 'Name of state/province/region/prefecture',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.city',
            'type' => 'string',
            'desc' => 'Name of city/town',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.postal_code',
            'type' => 'string',
            'desc' => "Postal or other code identifying organization's locale",
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.director_name',
            'type' => 'string',
            'desc' => 'Organization registered managing director',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.website',
            'type' => 'string',
            'desc' => 'Organization website',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.email',
            'type' => 'string',
            'desc' => 'Organization contact email',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'organization.phone',
            'type' => 'string',
            'desc' => 'Organization contact phone',
            'created_at' => now(),
        ]);
    }

    public function insertCardFields(): void
    {
        DB::table('sep12_fields')->insert([
            'key' => 'card.number',
            'type' => 'string',
            'desc' => 'Card number',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.expiration_date',
            'type' => 'date',
            'desc' => 'Expiration month and year in YY-MM format (e.g. 29-11, November 2029)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.cvc',
            'type' => 'string',
            'desc' => 'CVC number (Digits on the back of the card)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.holder_name',
            'type' => 'string',
            'desc' => 'Name of the card holder',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.network',
            'type' => 'string',
            'desc' => 'Brand of the card/network it operates within (e.g. Visa, Mastercard, AmEx, etc.)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.postal_code',
            'type' => 'string',
            'desc' => 'Billing address postal code',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.country_code',
            'type' => 'string',
            'desc' => 'Billing address country code in ISO 3166-1 alpha-2 code (e.g. US)',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.state_or_province',
            'type' => 'string',
            'desc' => 'Name of state/province/region/prefecture in ISO 3166-2 format',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.city',
            'type' => 'string',
            'desc' => 'Name of city/town',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.address',
            'type' => 'string',
            'desc' => 'Entire address (country, state, postal code, street address, etc...) as a multi-line string',
            'created_at' => now(),
        ]);

        DB::table('sep12_fields')->insert([
            'key' => 'card.token',
            'type' => 'string',
            'desc' => 'Token representation of the card in some external payment system (e.g. Stripe)',
            'created_at' => now(),
        ]);
    }
}