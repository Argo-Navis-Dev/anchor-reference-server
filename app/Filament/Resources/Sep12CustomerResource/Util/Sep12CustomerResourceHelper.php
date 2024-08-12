<?php

namespace App\Filament\Resources\Sep12CustomerResource\Util;

use App\Filament\Resources\Sep12CustomerResource;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;
use App\Models\Sep12TypeToFields;
use App\Stellar\Sep12Customer\Sep12CustomerType;
use App\Stellar\Sep12Customer\Sep12Helper;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use Illuminate\Support\Facades\Log;

class Sep12CustomerResourceHelper
{

    public static function populateCustomerFieldsBeforeFormLoad(array &$data, Sep12Customer $customerModel): void
    {
        $fields = Sep12Field::all();
        $fieldIDToBean = $fields->keyBy('id')->all();
        $prefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;
        Sep12ProvidedField::where('sep12_customer_id', $customerModel->id)->get()->each(function ($providedField) use (&$data, $fieldIDToBean, $prefix, $statusSuffix) {
            $field = $fieldIDToBean[$providedField->id];
            $name = "{$prefix}{$field->id}";
            $data[$name] = $providedField->string_value;
            $data["{$name}{$statusSuffix}"] = $providedField->status;
        });
    }

    public static function onCustomerStatusChanged(
        string $newState,
        Callable $set,
        Sep12Customer $customer): void {
        if($newState == CustomerStatus::ACCEPTED) {
            self::updateAllFieldStatus(
                ProvidedCustomerFieldStatus::ACCEPTED,
                $set,
                $customer
            );
        }
    }

    public static function onCustomerFieldStatusChanged(
        string $newState,
        Callable $set,
        Callable $get,
        Sep12Customer $customer): void {
        if($newState != ProvidedCustomerFieldStatus::ACCEPTED) {
            $set('status', CustomerStatus::PROCESSING);
        }else {
            $fieldStatusNames = self::getAllFieldStatusNames();
            $allFieldStatusesAccepted = true;
            foreach($fieldStatusNames as $fieldStatusName) {
                $fieldStatusValue = $get($fieldStatusName);
                if($fieldStatusValue != ProvidedCustomerFieldStatus::ACCEPTED) {
                    $allFieldStatusesAccepted = false;
                }
            }
            if($allFieldStatusesAccepted) {
                $set('status', CustomerStatus::ACCEPTED);
            }
        }
    }

    private static function updateAllFieldStatus(
        string $newFieldStatus,
        Callable $set,
        ?Sep12Customer $customer = null): void {
        $fieldNames = self::getAllFieldStatusNames($customer);
        foreach($fieldNames as $statusFieldName) {
            $set($statusFieldName, $newFieldStatus);
        }
    }

    private static function getAllFieldStatusNames(
        ?Sep12Customer $customer = null
    ): array {
        $type = Sep12CustomerType::DEFAULT;
        if ($customer !== null) {
            $type = $customer->type;
        }
        $sep12FieldsForType = Sep12Helper::getSep12FieldsForCustomerType($type);
        $allFields = [];
        $optionalFields = $sep12FieldsForType['optional'];
        if($optionalFields != null) {
            $allFields = array_merge($allFields, $optionalFields);
        }
        $requiredFields = $sep12FieldsForType['required'];
        if($requiredFields != null) {
            $allFields = array_merge($allFields, $requiredFields);
        }

        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;
        $fieldNames = [];
        foreach($allFields as $field) {
            $hasStatus = !isset(Sep12CustomerResource::KYC_FIELD_WITHOUT_STATUS[$field->key]);
            if($hasStatus) {
                $fieldNames[]  = "{$customFieldPrefix}{$field->id}{$statusSuffix}";
            }
        }
        return $fieldNames;
    }
}