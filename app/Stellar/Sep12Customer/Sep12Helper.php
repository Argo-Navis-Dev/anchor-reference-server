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
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Mail;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\StreamInterface;
use Illuminate\Support\Facades\Log;

use ArgoNavis\PhpAnchorSdk\callback\GetCustomerRequest;
use GuzzleHttp\Client;

class Sep12Helper
{

    /**
     * Composes a GetCustomerResponse object from the given optional Sep12Customer. If not provided,
     * creates a response with the data needed for registration.
     * @param Sep12Customer|null $customer customer data.
     * @return GetCustomerResponse response.
     */
    public static function buildCustomerResponse(?Sep12Customer $customer = null) : GetCustomerResponse {

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
        if(isset($sep12FieldsForType['required'])) {
            $requiredSep12Fields = $sep12FieldsForType['required'];
        }

        /**
         * @var array<Sep12Field> $optionalSep12Fields
         */
        $optionalSep12Fields = array();
        if(isset($sep12FieldsForType['optional'])) {
            $optionalSep12Fields = $sep12FieldsForType['optional'];
        }

        if ($customer !== null) {
            /**
             * @var array<string, ProvidedCustomerField> $providedCustomerFields
             */
            $providedCustomerFields = array();
            $response->status = $customer->status;
            $response->id = $customer->id;
            $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();
            foreach($sep12ProvidedFields as $sep12providedField) {

                foreach($requiredSep12Fields as $sep12Field) {
                    if ($sep12Field->id == $sep12providedField->sep12_field_id) {
                        $providedCustomerFields[$sep12Field->key] = self::buildProvidedCustomerFieldFromSep12Field($sep12Field,
                            $sep12providedField->status, $sep12providedField->error);
                        $requiredSep12Fields = array_diff($requiredSep12Fields, [$sep12Field]);
                        break;
                    }
                }

                foreach($optionalSep12Fields as $sep12Field) {
                    if ($sep12Field->id == $sep12providedField->sep12_field_id) {
                        $providedCustomerFields[$sep12Field->key] = self::buildProvidedCustomerFieldFromSep12Field($sep12Field,
                            $sep12providedField->status, $sep12providedField->error, optional: true);
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
            $customerFields[$sep12Field->key] = self::buildCustomerFieldFromSep12Field($sep12Field);
        }
        foreach ($optionalSep12Fields as $sep12Field) {
            $customerFields[$sep12Field->key] = self::buildCustomerFieldFromSep12Field($sep12Field, optional:true);
        }
        if (count($customerFields) > 0) {
            $response->fields = $customerFields;
        }

        return $response;
    }

    /**
     * Creates and saves a new Sep12Customer from the given PutCustomerRequest.
     * The id of the customer in the request mus be null.
     * The account id of the customer in the request must not be null
     * @throws AnchorFailure
     */
    public static function newSep12Customer(PutCustomerRequest $request) : Sep12Customer {

        if ($request->id !== null) {
            throw new AnchorFailure('can not create new customer if id is not null');
        }

        $accountId = $request->account;

        $customer = self::getSep12CustomerByAccountId($request->account, $request->memo, $request->type);
        if ($customer != null) {
            throw new AnchorFailure('customer already exists');
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
                $streamSize = $providedField->binary_value;
                LOG::info('File uploaded: '. $providedField->id . ' ' . ', Size: ' . $streamSize);

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
        // check if the customer provided any fields. if not, nothing to update.
        if ($request->kycFields === null && $request->kycUploadedFiles === null) {
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
        foreach($newProvidedFields as $newProvidedField) {

            if (isset($sep12ProvidedFieldsBySep12FieldId[$newProvidedField->sep12_field_id])) {

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
                $toInsertFields[] = $newProvidedField;
            }
        }

        if (count($toInsertFields) > 0) {
            // add the new ones
            foreach($toInsertFields as $newProvidedField) {
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
            // set status to processing so that the new data can be checked.
            $customer->status = CustomerStatus::PROCESSING;
        }

        $customer->save();
        $customer->refresh();
        if($customerStatus !== $customer->status) {
            self::onCustomerStatusChanged($customer);
        }
        return $customer;
    }

    /**
     * Checks the verification code provided and updates the field if it matches.
     * Currently only supports email verification.
     * @throws AnchorFailure if the provided code dose not natch.
     */
    public static function handleVerification(Sep12Customer $customer, array $verificationFields) : void {

        // for now, we only have to handle email verification.
        if (!isset($verificationFields['email_address_verification'])) {
            return;
        }
        $verificationCode = $verificationFields['email_address_verification'];

        // load all fields that the customer provided earlier
        $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();


        // check if the user provided an email address and if so update the verification
        if ($sep12ProvidedFields === null || count($sep12ProvidedFields) === 0) {
            return;
        }

        $sep12EmailField = Sep12Field::where('key', 'email_address')->first();
        if($sep12EmailField === null) {
            // there is no email field
            return;
        }

        foreach ($sep12ProvidedFields as $field) {
            if ($field->sep12_field_id == $sep12EmailField->id) {

                // check if it is already verified
                if ($field->verified
                    && $field->status !== ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED) {
                    return;
                }

                // check if the verification code matches
                if ($field->verification_code === $verificationCode) {
                    // ok, update
                    $field->verified = true;
                    $field->status = ProvidedCustomerFieldStatus::ACCEPTED;
                    $field->save();
                } else {
                    throw new AnchorFailure('invalid email verification code');
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
    public static function getSep12CustomerByAccountId(string  $accountId,
                                                       ?int $memo = null,
                                                       ?string $type = null) : ?Sep12Customer {
        $query = ['account_id' => $accountId];

        if (!str_starts_with($accountId, 'M')) { // if not a muxed account, memo is relevant.
            $query['memo'] = $memo; // memo null is possible here
        }

        if ($type != null) {
            $query['type'] = $type;
        } else {
            $query['type'] = Sep12CustomerType::DEFAULT;
        }

        return Sep12Customer::where($query)->first();
    }

    /** Checks if customer needs info.
     * @param Sep12Customer $customer the one
     * @param ?Collection | null $allSep12Fields optional collection of fields to be considered. If not provided, loads all fields from the db.
     * @return bool true if customer needs info.
     */
    private static function customerNeedsInfo(Sep12Customer $customer, ?Collection $allSep12Fields = null) : bool {
        // load all fields that the customer provided earlier
        $sep12ProvidedFields = Sep12ProvidedField::where('sep12_customer_id', $customer->id)->get();

        /**
         * @var array<int> $providedFieldsIds
         */
        $providedSep12FieldsIds = array();
        foreach($sep12ProvidedFields as $providedField) {
            $providedSep12FieldsIds[] = $providedField->sep12_field_id;
        }

        // check if the all required fields have been provided
        $fieldsForType = self::getSep12FieldsForCustomerType($customer->type, allSep12Fields: $allSep12Fields);
        if($fieldsForType !== null && isset($fieldsForType['required'])) {
            $requiredFields = $fieldsForType['required'];
            $providedRequiredFields = array();
            foreach($providedSep12FieldsIds as $providedSep12FieldId) {
                foreach($requiredFields as $requiredField) {
                    if ($requiredField->id === $providedSep12FieldId) {
                        $providedRequiredFields[] = $requiredField;
                        break;
                    }
                }
            }
            $diff = array_diff($requiredFields, $providedRequiredFields);
            if (count($diff) > 0) {
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
    private static function sendVerificationCode(Collection $allSep12Fields, array $fieldsThatRequireVerification) : void {
        // check if any automatic validation request can be sent.
        if (count($fieldsThatRequireVerification) > 0) {
            // currently only for emails
            $sep12EmailFieldId = null;
            foreach($allSep12Fields as $sep12Field) {
                if ($sep12Field->key === 'email_address') {
                    $sep12EmailFieldId = $sep12Field->id;
                    break;
                }
            }
            if ($sep12EmailFieldId !== null) {
                foreach($fieldsThatRequireVerification as $field) {
                    if($field->sep12_field_id === $sep12EmailFieldId) {
                        $emailAddress = $field->string_value;
                        $verificationCode = rand(100000, 999999); // set: 123456 - for test
                        $field->refresh();
                        $field->verification_code = strval($verificationCode);
                        $field->save();
                        Mail::to($emailAddress)->send(new Sep12EmailVerification($field->verification_code));
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
    private static function getSep12FieldsForCustomerType(string $type, ?Collection $allSep12Fields = null) : ?array {

        $typeFields = Sep12TypeToFields::where('type', $type)->first();

        if ($typeFields === null) {
            return null;
        }

        if ($typeFields->required_fields === null
            && $typeFields->optional_fields === null) {
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
                if(in_array($mField->key, $requiredFieldsKeys)) {
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
                if(in_array($mField->key, $optionalFieldsKeys)) {
                    $optionalFields[] = $mField;
                }
            }
            if (count($optionalFields) > 0) {
                $result['optional'] = $optionalFields;
            }
        }

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
    private static function createSep12ProvidedFieldsFromKycFields(string $customerId, array $kycFields, ?Collection  $allSep12Fields = null) : array {
        $mFields = $allSep12Fields;
        if ($mFields === null) {
            $mFields = Sep12Field::all();
        }
        $result = array();
        if (count($kycFields) > 0) {
            foreach ($kycFields as $kycFieldKey => $kycFieldValue) {
                foreach($mFields as $mField) {
                    if ($mField->key === $kycFieldKey) { // only allow known fields
                        $providedField = new Sep12ProvidedField;
                        $providedField->status = ProvidedCustomerFieldStatus::PROCESSING;
                        if ($mField->requires_verification) {
                            $providedField->status = ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED;
                        }
                        $providedField->sep12_customer_id = $customerId;
                        $providedField->sep12_field_id = $mField->id;

                        // check if the field contains an uploaded file.
                        if ($kycFieldValue instanceof UploadedFileInterface) {
                            if ($mField->type !== 'binary') {
                                throw new AnchorFailure($kycFieldKey . ' must be ' . $mField->type);
                            }
                            if ($kycFieldValue->getError() === UPLOAD_ERR_INI_SIZE) {
                                throw new AnchorFailure($kycFieldKey . ' too large');
                            } elseif ($kycFieldValue->getError() !== UPLOAD_ERR_OK) {
                                throw new AnchorFailure($kycFieldKey . 'could not be uploaded.');
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
                            if($dateTime === false) {
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
            }
        }
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
    ) : CustomerField {
        $fieldName = $field->key;
        $type = $field->type;
        $desc = $field->desc;
        $choices = null;
        if ($field->choices != null) {
            $choices = array_map('trim', explode(',', $field->choices));
        }
        return new CustomerField($fieldName, $type, $desc, $choices, $optional);
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
    ) : ProvidedCustomerField {
        $fieldName = $field->key;
        $type = $field->type;
        $desc = $field->desc;
        $choices = null;
        if ($field->choices != null) {
            $choices = array_map('trim', explode(',', $field->choices));
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
        LOG::debug('Handling the customer status change callback: ' . $newStatus . ' callback URL: ' . $callbackUrl);
        if ($callbackUrl && !empty($callbackUrl)) {        
            $getCustomerRequest = new GetCustomerRequest($customer->account_id, $customer->memo);
            $customerIntegration = new CustomerIntegration();
            $sep12CustomerData = $customerIntegration->getCustomer($getCustomerRequest);
                        
            $httpClient = new Client();
            $response = $httpClient->post($customer->callback_url, [
                'json' => $sep12CustomerData
            ]);
            // Check the response status code
            if ($response->getStatusCode() == 200) {
                LOG::debug('The customer status change callback has been called successfully!');
            } else {
                LOG::error('Failed to call the customer status change callback!');         
            }
            if($newStatus === CustomerStatus::ACCEPTED ||
               $newStatus === CustomerStatus::REJECTED) {
                $customer->callback_url = null;
                $customer->save();
                $customer->refresh();
            }
        }
    }
}
