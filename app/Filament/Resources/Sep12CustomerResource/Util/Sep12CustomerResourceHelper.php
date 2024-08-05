<?php

namespace App\Filament\Resources\Sep12CustomerResource\Util;

use App\Filament\Resources\Sep12CustomerResource;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;

class Sep12CustomerResourceHelper
{

    public static function populateCustomerFieldsBeforeFormLoad(array &$data, Sep12Customer $customerModel)
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

}