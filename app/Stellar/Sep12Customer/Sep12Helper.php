<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.


namespace App\Stellar\Sep12Customer;

use App\Mail\Sep12EmailVerification;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;
use App\Models\Sep12TypeToFields;
use ArgoNavis\PhpAnchorSdk\callback\GetCustomerResponse;
use ArgoNavis\PhpAnchorSdk\callback\PutCustomerRequest;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\shared\CustomerField;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerField;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use DateTime;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Psr\Http\Message\UploadedFileInterface;
use Illuminate\Support\Facades\Log;

use Soneso\StellarSDK\Crypto\KeyPair;

use ArgoNavis\PhpAnchorSdk\callback\GetCustomerRequest;
use GuzzleHttp\Client;

use function json_encode;

class Sep12Helper
{

    /**
     * Composes a GetCustomerResponse object from the given optional Sep12Customer. If not provided,
     * creates a response with the data needed for registration.
     * @param Sep12Customer|null $customer customer data.
     * @return GetCustomerResponse response.
     */
    public static function buildCustomerResponse(
        ?Sep12Customer $customer = null,
        ?string $lang = 'en',
    ) : GetCustomerResponse {
        Log::debug(
            'Converting db customer record to customer response model',
            ['context' => 'sep12', 'customer_db_record' => json_encode($customer)],
        );

        $response = new GetCustomerResponse(CustomerStatus::NEEDS_INFO);
        $type = Sep12CustomerType::DEFAULT;
        if ($customer !== null) {
            $type = $customer->type;
            $response->status = $customer->status;
        }

        $sep12FieldsForType = self::getSep12FieldsForCustomerType($type);

        /**
         * @var array<Sep12Field> $requiredSep12Fields
         */
        $requiredSep12Fields = array();
        if (isset($sep12FieldsForType['required'])) {
            $requiredSep12Fields = $sep12FieldsForType['required'];
        }

        /**
         * @var array<Sep12Field> $optionalSep12Fields
         */
        $optionalSep12Fields = array();
        if (isset($sep12FieldsForType['optional'])) {
            $optionalSep12Fields = $sep12FieldsForType['optional'];
        }
        Log::debug(
            'The list of fields.',
            ['context' => 'sep12', 'required_fields' => json_encode($requiredSep12Fields),
                'optional_fields' => json_encode($optionalSep12Fields)],
        );

        if ($customer !== null) {
            /**
             * @var array<string, ProvidedCustomerField> $providedCustomerFields
             */
            $providedCustomerFields = array();
            $response->status = $customer->status;
            $response->id = $customer->id;
            $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();
            Log::debug(
                'The list of provided fields.',
                ['context' => 'sep12', 'provided_fields' => json_encode($sep12ProvidedFields)],
            );
            foreach ($sep12ProvidedFields as $sep12providedField) {
                foreach ($requiredSep12Fields as $sep12Field) {
                    if ($sep12Field->id == $sep12providedField->sep12_field_id) {
                        $providedCustomerFields[$sep12Field->key] = self::buildProvidedCustomerFieldFromSep12Field(
                            $sep12Field,
                            $sep12providedField->status,
                            $sep12providedField->error,
                            null,
                            $lang,
                        );
                        $requiredSep12Fields = array_diff($requiredSep12Fields, [$sep12Field]);
                        break;
                    }
                }

                foreach ($optionalSep12Fields as $sep12Field) {
                    if ($sep12Field->id == $sep12providedField->sep12_field_id) {
                        $providedCustomerFields[$sep12Field->key] = self::buildProvidedCustomerFieldFromSep12Field(
                            $sep12Field,
                            $sep12providedField->status,
                            $sep12providedField->error,
                            optional: true,
                            lang: $lang,
                        );
                        $optionalSep12Fields = array_diff($optionalSep12Fields, [$sep12Field]);
                        break;
                    }
                }
            }
            $response->providedFields = $providedCustomerFields;
        }

        /**
         * @var array<string, CustomerField> $customerFields
         */
        $customerFields = array();
        foreach ($requiredSep12Fields as $sep12Field) {
            $customerFields[$sep12Field->key] = self::buildCustomerFieldFromSep12Field(
                $sep12Field,
                null,
                $lang,
            );
        }
        foreach ($optionalSep12Fields as $sep12Field) {
            $customerFields[$sep12Field->key] = self::buildCustomerFieldFromSep12Field(
                field: $sep12Field,
                optional: true,
                lang: $lang,
            );
        }
        if (count($customerFields) > 0) {
            $response->fields = $customerFields;
        }
        Log::debug(
            'The converted customer response model',
            ['context' => 'sep12', 'model' => json_encode($response)],
        );

        return $response;
    }

    /**
     * Creates and saves a new Sep12Customer from the given PutCustomerRequest.
     * The id of the customer in the request mus be null.
     * The account id of the customer in the request must not be null
     * @throws AnchorFailure
     */
    public static function newSep12Customer(PutCustomerRequest $request) : Sep12Customer
    {
        Log::debug(
            'Saving new customer record out of the passed request.',
            ['context' => 'sep12', 'operation' => 'put_customer', 'request' => json_encode($request)],
        );

        if ($request->id !== null) {
            throw new AnchorFailure(
                message: 'can not create new customer if id is not null',
                messageKey: 'sep12_lang.error.new_customer_failed_with_id_specified',
            );
        }

        $accountId = $request->account;

        $customer = self::getSep12CustomerByAccountId($request->account, $request->memo, $request->type);
        if ($customer != null) {
            throw new AnchorFailure(
                message: 'customer already exists',
                messageKey: 'sep12_lang.error.customer_exists',
            );
        }

        // create a new customer
        $customer = new Sep12Customer;
        $customer->status = CustomerStatus::PROCESSING;
        $customer->account_id = $accountId;
        $customer->memo = $request->memo;

        if ($request->type != null) {
            $customer->type = $request->type;
        }

        // save the customer
        $customer->save();
        $customer->refresh();

        // add the provided fields for the customer
        /**
         * @var array<Sep12ProvidedField> $fieldsThatRequireVerification
         */
        $fieldsThatRequireVerification = array();


        $allSep12Fields = Sep12Field::all();

        // check if the customer provided any fields
        if ($request->kycFields !== null || $request->kycUploadedFiles !== null) {
            $noUploadedFiles = $request->kycUploadedFiles != null ?
                count($request->kycUploadedFiles) : 0;
            Log::debug(
                'Updating customer provided fields.',
                ['context' => 'sep12', 'operation' => 'put_customer',
                    'fields' => json_encode($request->kycFields), 'no_uploaded_files' => $noUploadedFiles],
            );

            $kycData = array();
            if ($request->kycFields !== null) {
                $kycData = array_merge($kycData, $request->kycFields);
            }
            if ($request->kycUploadedFiles !== null) {
                $kycData = array_merge($kycData, $request->kycUploadedFiles);
            }

            // create the db objects needed
            $providedFields = self::createSep12ProvidedFieldsFromKycFields($customer->id, $kycData, allSep12Fields: $allSep12Fields);
            foreach ($providedFields as $providedField) {
                // save the field.
                $providedField->save();
                // check if the field requires verification
                if ($providedField->status === ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED) {
                    $fieldsThatRequireVerification[] = $providedField;
                }
            }
        }
        if (self::customerNeedsInfo($customer, $allSep12Fields)) {
            $customer->status = CustomerStatus::NEEDS_INFO;
            $customer->save();
            $customer->refresh();
        } else {
            $autoAccept = config('stellar.sep12.auto_accept');
            Log::debug(
                'Is auto accept customer turned on.',
                ['context' => 'sep12', 'operation' => 'put_customer', 'auto_accept' => $autoAccept],
            );

            if ($autoAccept === 'true') {
                $customer->status = CustomerStatus::ACCEPTED;
                $customer->save();
                $customer->refresh();
            }
        }

        // check if any automatic validation request can be sent.
        self::sendVerificationCode($allSep12Fields, $fieldsThatRequireVerification);

        //Call the status change callback.
        self::onCustomerStatusChanged($customer);

        return $customer;
    }

    /**
     * Updates the customer data by using the data from the given put request.
     * @param Sep12Customer $customer the customer to update
     * @param PutCustomerRequest $request the put request.
     * @return Sep12Customer the updated customer.
     * @throws AnchorFailure if an error occurs e.g. invalid request data.
     */
    public static function updateSep12Customer(Sep12Customer $customer, PutCustomerRequest $request) : Sep12Customer
    {
        Log::debug(
            'Updating existing customer.',
            ['context' => 'sep12', 'operation' => 'put_customer',
                'request' => json_encode($request), 'customer_db_model' => json_encode($customer)],
        );

        // check if the customer provided any fields. if not, nothing to update.
        if ($request->kycFields === null && $request->kycUploadedFiles === null) {
            Log::warning(
                'Neither provided fields nor uploaded files are not specified.',
                ['context' => 'sep12', 'operation' => 'put_customer'],
            );

            return $customer;
        }

        // load all fields that the customer provided earlier
        $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();

        // load all known sep12 fields
        $allSep12Fields = Sep12Field::all();

        /**
         * @var array<Sep12ProvidedField> $fieldsThatRequireVerification
         */
        $fieldsThatRequireVerification = array();

        /**
         * @var array<string, Sep12Field> $sep12ProvidedFieldsByKey
         */
        $sep12ProvidedFieldsBySep12FieldId = array();
        foreach ($sep12ProvidedFields as $providedField) {
            $sep12ProvidedFieldsBySep12FieldId[$providedField->sep12_field_id] = $providedField;
        }

        // create the provided fields from the new data
        $kycData = array();
        if ($request->kycFields !== null) {
            $kycData = array_merge($kycData, $request->kycFields);
        }
        if ($request->kycUploadedFiles !== null) {
            $kycData = array_merge($kycData, $request->kycUploadedFiles);
        }

        $newProvidedFields = self::createSep12ProvidedFieldsFromKycFields($customer->id, $kycData, allSep12Fields: $allSep12Fields);

        /**
         * @var array<Sep12ProvidedField> $toInsertFields
         */
        $toInsertFields = array();

        // update the fields that we already have
        foreach ($newProvidedFields as $newProvidedField) {
            if (isset($sep12ProvidedFieldsBySep12FieldId[$newProvidedField->sep12_field_id])) {
                Log::debug(
                    'Updating existing field.',
                    ['context' => 'sep12', 'operation' => 'put_customer',
                        'field_id' => $newProvidedField->sep12_field_id],
                );

                // update
                $existing = $sep12ProvidedFieldsBySep12FieldId[$newProvidedField->sep12_field_id];
                $existing->status = $newProvidedField->status;
                $existing->error = $newProvidedField->error;
                $existing->string_value = $newProvidedField->string_value;
                $existing->number_value = $newProvidedField->number_value;
                $existing->date_value = $newProvidedField->date_value;
                $existing->verification_code = $newProvidedField->verification_code;
                $existing->verified = false;
                $existing->save();
                $existing->refresh();

                // check if the field requires verification
                if ($newProvidedField->status === ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED) {
                    $fieldsThatRequireVerification[] = $existing;
                }
            } else {
                Log::debug(
                    'Adding new field.',
                    ['context' => 'sep12', 'operation' => 'put_customer',
                        'field_id' => $newProvidedField->sep12_field_id],
                );

                $toInsertFields[] = $newProvidedField;
            }
        }

        if (count($toInsertFields) > 0) {
            // add the new ones
            foreach ($toInsertFields as $newProvidedField) {
                $newProvidedField->save();

                // check if the field requires verification
                if ($newProvidedField->status === ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED) {
                    $newProvidedField->refresh();
                    $fieldsThatRequireVerification[] = $newProvidedField;
                }
            }
        }

        // check if any automatic validation request can be sent.
        self::sendVerificationCode($allSep12Fields, $fieldsThatRequireVerification);
        $customerStatus = $customer->status;
        // check if the customer still needs to send info
        if (self::customerNeedsInfo($customer, $allSep12Fields)) {
            $customer->status = CustomerStatus::NEEDS_INFO;
        } else {
            $autoAccept = config('stellar.sep12.auto_accept');
            Log::debug(
                'Is auto accept customer turned on.',
                ['context' => 'sep12', 'operation' => 'put_customer', 'auto_accept' => $autoAccept],
            );

            if ($autoAccept === 'true') {
                $customer->status = CustomerStatus::ACCEPTED;
            } else {
                // set status to processing so that the new data can be checked.
                $customer->status = CustomerStatus::PROCESSING;
            }
        }

        $customer->save();
        $customer->refresh();
        if ($customerStatus !== $customer->status) {
            self::onCustomerStatusChanged($customer);
        }
        return $customer;
    }

    /**
     * Checks the verification code provided and updates the field if it matches.
     * Currently only supports email verification.
     * @throws AnchorFailure if the provided code dose not natch.
     */
    public static function handleVerification(Sep12Customer $customer, array $verificationFields) : void
    {
        Log::debug(
            'Handling customer verification.',
            ['context' => 'sep12', 'operation' => 'put_customer_verification',
                'customer' => json_encode($customer), 'verification_fields' => json_encode($verificationFields)],
        );

        // for now, we only have to handle email verification.
        if (!isset($verificationFields['email_address_verification'])) {
            Log::warning(
                'Nothing to verificate, only email verification is implemented.',
                ['context' => 'sep12', 'operation' => 'put_customer_verification'],
            );

            return;
        }
        $verificationCode = $verificationFields['email_address_verification'];

        // load all fields that the customer provided earlier
        $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();


        // check if the user provided an email address and if so update the verification
        if ($sep12ProvidedFields === null || count($sep12ProvidedFields) === 0) {
            Log::warning(
                'The customer does not have any provided fields.',
                ['context' => 'sep12', 'operation' => 'put_customer_verification'],
            );

            return;
        }

        $sep12EmailField = Sep12Field::where('key', 'email_address')->first();
        if ($sep12EmailField === null) {
            // there is no email field
            Log::warning(
                'The customer email is not yet submitted.',
                ['context' => 'sep12', 'operation' => 'put_customer_verification'],
            );

            return;
        }

        foreach ($sep12ProvidedFields as $field) {
            if ($field->sep12_field_id == $sep12EmailField->id) {
                // check if it is already verified
                if ($field->verified
                    && $field->status !== ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED) {
                    Log::warning(
                        'The customer email is already verified.',
                        ['context' => 'sep12', 'operation' => 'put_customer_verification', 'status' => $field->status],
                    );

                    return;
                }
                Log::debug(
                    'Comparing the passed and the DB verification codes.',
                    ['context' => 'sep12', 'operation' => 'put_customer_verification',
                        'verification_code' => $verificationCode,
                        'db_verification_code' => $field->db_verification_code,
                    ],
                );

                // check if the verification code matches
                if ($field->verification_code === $verificationCode) {
                    // ok, update
                    $field->verified = true;
                    $field->status = ProvidedCustomerFieldStatus::ACCEPTED;
                    $field->save();
                } else {
                    throw new AnchorFailure(
                        message: 'invalid email verification code',
                        messageKey: 'sep12_lang.error.invalid_email_verification_code',
                    );
                }
                break;
            }
        }
    }
    /**
     * Loads a customer from the db for the given data.
     * @param string $accountId account id of the customer.
     * @param int|null $memo memo that identifies the customer, if any.
     * @param string|null $type type of the customer if any.
     * @return Sep12Customer|null The customer if found.
     */
    public static function getSep12CustomerByAccountId(
        string  $accountId,
        ?int $memo = null,
        ?string $type = null
    ) : ?Sep12Customer {
        $query = ['account_id' => $accountId];

        if (!str_starts_with($accountId, 'M')) { // if not a muxed account, memo is relevant.
            $query['memo'] = $memo; // memo null is possible here
        }

        if ($type != null) {
            $query['type'] = $type;
        } else {
            $query['type'] = Sep12CustomerType::DEFAULT;
        }
        Log::debug(
            'Loading customer by account id.',
            ['context' => 'sep12', 'query' => json_encode($query)],
        );

        return Sep12Customer::where($query)->first();
    }

    /** Checks if customer needs info.
     * @param Sep12Customer $customer the one
     * @param ?Collection | null $allSep12Fields optional collection of fields to be considered.
     * If not provided, loads all fields from the db.
     * @return bool true if customer needs info.
     */
    private static function customerNeedsInfo(Sep12Customer $customer, ?Collection $allSep12Fields = null) : bool
    {
        // load all fields that the customer provided earlier
        $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();
        Log::debug(
            'Verify if customer needs to provide info.',
            ['context' => 'sep12', 'customer' => json_encode($customer),
                'fields' => json_encode($allSep12Fields), 'provided_fields' => json_encode($sep12ProvidedFields)],
        );

        /**
         * @var array<int> $providedFieldsIds
         */
        $providedSep12FieldsIds = array();
        foreach ($sep12ProvidedFields as $providedField) {
            $providedSep12FieldsIds[] = $providedField->sep12_field_id;
        }

        // check if the all required fields have been provided
        $fieldsForType = self::getSep12FieldsForCustomerType($customer->type, allSep12Fields: $allSep12Fields);
        if ($fieldsForType !== null && isset($fieldsForType['required'])) {
            $requiredFields = $fieldsForType['required'];
            $providedRequiredFields = array();
            foreach ($providedSep12FieldsIds as $providedSep12FieldId) {
                foreach ($requiredFields as $requiredField) {
                    if ($requiredField->id === $providedSep12FieldId) {
                        $providedRequiredFields[] = $requiredField;
                        break;
                    }
                }
            }
            $diff = array_diff($requiredFields, $providedRequiredFields);
            $noDiff = count($diff);
            Log::debug(
                'The list of missing fields.',
                ['context' => 'sep12', 'missing_fields' => json_encode($diff), 'no_missing_fields' => $noDiff],
            );

            if ($noDiff > 0) {
                // if not all required fields provided, then set the customer status to needs info.
                return true;
            }
        }
        return false;
    }

    /**
     * @param Collection $allSep12Fields
     * @param array<Sep12ProvidedField> $fieldsThatRequireVerification
     * @return void
     */
    private static function sendVerificationCode(Collection $allSep12Fields, array $fieldsThatRequireVerification) : void
    {
        $noFieldsThatRequireVerification = count($fieldsThatRequireVerification);
        Log::debug(
            'Sending verification code by fields.',
            ['context' => 'sep12', 'no_fields_that_require_verification' => $noFieldsThatRequireVerification,
                'fields_that_require_verification' => json_encode($fieldsThatRequireVerification)],
        );

        // check if any automatic validation request can be sent.
        if ($noFieldsThatRequireVerification > 0) {
            // currently only for emails
            $sep12EmailFieldId = null;
            foreach ($allSep12Fields as $sep12Field) {
                if ($sep12Field->key === 'email_address') {
                    $sep12EmailFieldId = $sep12Field->id;
                    break;
                }
            }
            if ($sep12EmailFieldId !== null) {
                foreach ($fieldsThatRequireVerification as $field) {
                    if ($field->sep12_field_id === $sep12EmailFieldId) {
                        $emailAddress = $field->string_value;
                        $verificationCode = rand(100000, 999999); // set: 123456 - for test
                        $field->refresh();
                        $field->verification_code = strval($verificationCode);
                        $field->save();
                        Mail::to($emailAddress)->send(new Sep12EmailVerification($field->verification_code));
                        Log::debug(
                            'Sending verification code.',
                            ['context' => 'sep12', 'email_address' => $emailAddress,
                                'verification_code' => $verificationCode, 'field' => $field->id],
                        );

                        break;
                    }
                }
            }
        }
    }


    /**
     * Returns the Sep12Fields associated with the given customer type.
     * @param string $type customer type
     * @param ?Collection | null $allSep12Fields optional collection of fields to be considered. If not provided, loads all fields from the db.
     * @return ?array<string, array<Sep12Field> containing the found Sep12Fields.
     * keys: 'required', 'optional' and 'requires_verification'. null if nothing found.
     */
    public static function getSep12FieldsForCustomerType(string $type, ?Collection $allSep12Fields = null) : ?array
    {
        Log::debug(
            'Retrieving SEP-12 fields by type.',
            ['context' => 'sep12', 'type' => $type],
        );
        $typeFields = Sep12TypeToFields::where('type', $type)->first();

        if ($typeFields === null) {
            Log::warning(
                'There are no SEP-12 field association defined by the passed type.',
                ['context' => 'sep12', 'type' => $type],
            );

            return null;
        }

        if ($typeFields->required_fields === null
            && $typeFields->optional_fields === null) {
            Log::warning(
                'There are no SEP-12 field association defined by the passed type.',
                ['context' => 'sep12', 'type' => $type],
            );

            return null;
        }
        /**
         * @var array<string, array<Sep12Field> $result
         */
        $result = array();
        $mFields = $allSep12Fields;
        if ($mFields === null) {
            $mFields = Sep12Field::all();
        }
        if ($typeFields->required_fields !== null) {
            /**
             * @var array<Sep12Field> $requiredFields
             */
            $requiredFields = array();
            $requiredFieldsKeys = array_map('trim', explode(',', $typeFields->required_fields));

            foreach ($mFields as $mField) {
                if (in_array($mField->key, $requiredFieldsKeys)) {
                    $requiredFields[] = $mField;
                }
            }
            if (count($requiredFields) > 0) {
                $result['required'] = $requiredFields;
            }
        }

        if ($typeFields->optional_fields !== null) {
            /**
             * @var array<Sep12Field> $optionalFields
             */
            $optionalFields = array();
            $optionalFieldsKeys = array_map('trim', explode(',', $typeFields->optional_fields));
            foreach ($mFields as $mField) {
                if (in_array($mField->key, $optionalFieldsKeys)) {
                    $optionalFields[] = $mField;
                }
            }
            if (count($optionalFields) > 0) {
                $result['optional'] = $optionalFields;
            }
        }
        Log::debug(
            'The retrieved SEP-12 fields by type.',
            ['context' => 'sep12', 'fields' => json_encode($result), 'type' => $type],
        );

        if (count($result) == 0) {
            return null;
        }

        return $result;
    }

    /**
     * Creates Sep12ProvidedFields objects from the given customer id and kyc fields from request.
     * kyc fields values can be normal data or uploaded files <key,value>
     * @param string $customerId id of the customer to create the models for.
     * @param array<array-key , mixed> | array<array-key , UploadedFileInterface> $kycFields kyc fields containing key  => normal values or UploadedFileInterface values from the put request.
     * @param ?Collection | null $allSep12Fields all sep12 fields to be considered if available. If not provided it will load all from the db.
     * @return array<Sep12ProvidedField> containing the created Sep12ProvidedField models.
     * @throws AnchorFailure if the corresponding Sep12Field in the database has an unknown type (not: string, number, date or binary) or if the kyc field from the request is not valid (e.g. has a wrong type).
     */
    private static function createSep12ProvidedFieldsFromKycFields(string $customerId, array $kycFields, ?Collection  $allSep12Fields = null) : array
    {
        $mFields = $allSep12Fields;
        Log::debug(
            'Creating SEP-12 provided fields model out of passed KYC fields data.',
            ['context' => 'sep12', 'customer_id' => $customerId, 'kyc_fields' => json_encode($kycFields),
                'fields' => json_encode($allSep12Fields)],
        );

        if ($mFields === null) {
            $mFields = Sep12Field::all();
        }
        $result = array();
        if (count($kycFields) > 0) {
            foreach ($kycFields as $kycFieldKey => $kycFieldValue) {
                $found = false;
                foreach ($mFields as $mField) {
                    if ($mField->key === $kycFieldKey) { // only allow known fields
                        $found = true;
                        $providedField = new Sep12ProvidedField;
                        $providedField->status = ProvidedCustomerFieldStatus::PROCESSING;
                        if ($mField->requires_verification) {
                            $providedField->status = ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED;
                        } else {
                            $autoAccept = config('stellar.sep12.auto_accept');
                            if ($autoAccept === 'true') {
                                $providedField->status = ProvidedCustomerFieldStatus::ACCEPTED;
                            }
                        }
                        $providedField->sep12_customer_id = $customerId;
                        $providedField->sep12_field_id = $mField->id;

                        // check if the field contains an uploaded file.
                        if ($kycFieldValue instanceof UploadedFileInterface) {
                            if ($mField->type !== 'binary') {
                                throw new AnchorFailure(
                                    message: $kycFieldKey . ' must be ' . $mField->type,
                                    messageKey: 'sep12_lang.error.invalid_kyc_field',
                                    messageParams: ['field' => $kycFieldKey, 'type' => $mField->type],
                                );
                            }
                            if ($kycFieldValue->getError() === UPLOAD_ERR_INI_SIZE) {
                                throw new AnchorFailure($kycFieldKey . ' too large');
                            } elseif ($kycFieldValue->getError() !== UPLOAD_ERR_OK) {
                                Log::debug(
                                    'Incorrect binary field.',
                                    ['context' => 'sep12', 'error' => $kycFieldValue->getError()],
                                );
                                throw new AnchorFailure(
                                    message: $kycFieldKey . 'could not be uploaded.',
                                    messageKey: 'sep12_lang.error.file_could_not_be_uploaded',
                                );
                            }
                            $fileContents =  $kycFieldValue->getStream()->getContents();
                            $providedField->binary_value = $fileContents;
                            $result[] = $providedField;
                            continue;
                        }

                        // normal field, not an uploaded file.
                        if ($mField->type === 'string') {
                            if ($mField->key === 'email_address') {
                                if (!filter_var($kycFieldValue, FILTER_VALIDATE_EMAIL)) {
                                    throw new AnchorFailure($kycFieldKey . ' is not a valid email address');
                                }
                            }
                            $providedField->string_value = $kycFieldValue;
                        } elseif ($mField->type === 'number') {
                            if (!is_numeric($kycFieldValue)) {
                                throw new AnchorFailure($kycFieldKey . ' is not a number');
                            }
                            $providedField->number_value = intval($kycFieldValue);
                        } elseif ($mField->type === 'date') {
                            $dateTime = DateTime::createFromFormat(DATE_ATOM, $kycFieldValue);
                            if ($dateTime === false) {
                                throw new AnchorFailure($kycFieldKey . ' is not a valid ISO 8601 date');
                            }
                            $providedField->date_value = $dateTime;
                        } elseif ($mField->type === 'binary') {
                            $providedField->binary_value = $kycFieldValue;
                        } else {
                            throw new AnchorFailure('unknown field type ' . $mField->type);
                        }
                        $result[] = $providedField;
                    }
                }
                if (!$found) {
                    Log::warning(
                        'Unknown KYC field.',
                        ['context' => 'sep12', 'field' => $kycFieldKey],
                    );
                }
            }
        }
        Log::debug(
            'The list of provided fields model.',
            ['context' => 'sep12', 'result' => json_encode($result)],
        );

        return $result;
    }

    /**
     * Builds a CustomerField from the given data.
     * @param Sep12Field $field
     * @param bool|null $optional
     * @return CustomerField
     */
    private static function buildCustomerFieldFromSep12Field(
        Sep12Field $field,
        ?bool $optional = null,
        ?string $lang = 'en',
    ) : CustomerField {
        $fieldName = $field->key;
        $type = $field->type;
        $descriptionKey = 'sep12_lang.label.' . $field->key . '.description';
        $desc = __($descriptionKey, [], $lang);
        if ($desc === $descriptionKey) {
            $desc = $field->desc;
        }
        
        $choices = null;
        if ($field->choices != null) {
            $choices = array_map('trim', explode(',', $field->choices));
            $choices = self::getLocalizedFieldChoices($field, $choices, $lang);
        }
        return new CustomerField($fieldName, $type, $desc, $choices, $optional);
    }

    /**
     * Returns the localized choices for the given field.
     *
     * @param Sep12Field $field the field
     * @param array<string> $choices the choices
     * @return array<string> the localized choices
     */
    private static function getLocalizedFieldChoices(
        Sep12Field $field,
        array $choices,
        ?string $lang = 'en',
    ) : array {
        $localizedChoices = [];
        for ($i = 0; $i < count($choices); $i++) {
            $choice = $choices[$i];
            $convertedChoice = trim($choice);
            $convertedChoice = strtolower($convertedChoice);
            $convertedChoice = preg_replace('/\s+/', '_', $convertedChoice);

            $localizationKey = "sep12_lang.label.{$field->key}.{$convertedChoice}";
            $localizedLabel = __($localizationKey, [], $lang);

            if ($localizationKey == $localizedLabel) {
                $localizedLabel = $choice;
            }
            $localizedChoices[$i] = $localizedLabel;
        }

        return $localizedChoices;
    }

    /**
     * Builds a ProvidedCustomerField object from the given data.
     * @param Sep12Field $field
     * @param string|null $status
     * @param string|null $error
     * @param bool|null $optional
     * @return ProvidedCustomerField
     */
    private static function buildProvidedCustomerFieldFromSep12Field(
        Sep12Field $field,
        ?string    $status = null,
        ?string    $error = null,
        ?bool      $optional = null,
        ?string    $lang = 'en',
    ) : ProvidedCustomerField {
        $fieldName = $field->key;
        $type = $field->type;
        $choices = null;
        $descriptionKey = 'sep12_lang.label.' . $field->key . '.description';
        $desc = __($descriptionKey, [], $lang);
        if ($desc === $descriptionKey) {
            $desc = $field->desc;
        }
        if ($field->choices != null) {
            $choices = array_map('trim', explode(',', $field->choices));
            $choices = self::getLocalizedFieldChoices($field, $choices, $lang);
        }
        return new ProvidedCustomerField($fieldName, $type, $desc, $choices, $optional, $status, $error);
    }

    /**
     * Calls the customer status change callback if the customer has a callback url.
     * Will submit POST requests until the user's status changes to ACCEPTED or REJECTED.
     * If so, remove the callback_url field from the DB.
     * (This method might saves and refreshes the customer object, previously updated and not saved customer object field is lost).
     * Should be called only after the customer status has been changed.
     * @param Sep12Customer $customer the customer to handle.
     * @return void
     */
    public static function onCustomerStatusChanged(Sep12Customer $customer)
    {
        $callbackUrl = $customer->callback_url;
        $newStatus = $customer->status;
        if (!empty($callbackUrl)) {
            $getCustomerRequest = new GetCustomerRequest($customer->account_id, $customer->memo);
            $customerIntegration = new CustomerIntegration();
            $sep12CustomerData = $customerIntegration->getCustomer($getCustomerRequest);
            Log::debug(
                'Handling customer status change.',
                ['context' => 'sep12', 'new_status' => $newStatus, 'customer_id' => $customer->id,
                    'callback_url' =>  $callbackUrl, 'data' => json_encode($sep12CustomerData),
                ],
            );

            $signature = self::getCallbackSignatureHeader($sep12CustomerData, $callbackUrl);
            $httpClient = new Client();
            try {
                $response = $httpClient->post($customer->callback_url, [
                    'headers' => [
                        'Signature' => $signature,
                        'X-Stellar-Signature' => $signature, //Deprecated
                    ],
                    'json' => $sep12CustomerData
                ]);
                // Check the response status code
                if ($response->getStatusCode() == 200) {
                    Log::debug(
                        'The customer status change callback has been called successfully!',
                        ['context' => 'sep12', 'customer_id' => $customer->id, 'callback_url' => $callbackUrl],
                    );
                } else {
                    Log::error(
                        'Failed to call the customer status change callback.',
                        ['context' => 'sep12', 'http_status_code' => $response->getStatusCode(),
                            'callback_url' => $callbackUrl,
                        ],
                    );
                }
            } catch (RequestException $e) {
                $responseBody = '';
                if ($e->hasResponse()) {
                    $responseBody = $e->getResponse()->getBody();
                }
                Log::error(
                    'Failed to call the customer status change callback.',
                    ['context' => 'sep12', 'error' => $e->getMessage(),
                        'exception' => $e, 'body' => json_encode($responseBody), 'callback_url' => $callbackUrl],
                );
            }

            if ($newStatus === CustomerStatus::ACCEPTED ||
               $newStatus === CustomerStatus::REJECTED) {
                $customer->callback_url = null;
                $customer->save();
                $customer->refresh();
            }
        } else {
            Log::debug(
                'Customer status change callback URL is null, no callback execution action is needed.',
                ['context' => 'sep12'],
            );
        }
    }

    /**
     * Returns the signature header value for the customer callback.
     *
     * @param GetCustomerResponse $sep12CustomerData the customer data to be sent in request body.
     * @param string $callbackUrl the callback URL.
     */
    private static function getCallbackSignatureHeader(
        GetCustomerResponse $sep12CustomerData,
        string $callbackUrl
    ): string {
        $signingSeed = config('stellar.server.server_account_signing_key');
        $anchorKeys = KeyPair::fromSeed($signingSeed);
        $currentTime = round(microtime(true));
        $signature = $currentTime . '.' . $callbackUrl . '.' . json_encode($sep12CustomerData);
        Log::debug(
            'The SEP-12 status change callback header signature to be signed.',
            ['context' => 'sep12', 'signature' => $signature],
        );
        $signature = $anchorKeys->sign($signature);
        $based64Signature = base64_encode($signature);
        $signatureHeader = 't=' . $currentTime . ', s=' . $based64Signature;
        Log::debug(
            'The SEP-12 status change callback header signed signature.',
            ['context' => 'sep12', 'signed_signature' => $signatureHeader],
        );

        return $signatureHeader;
    }
}
