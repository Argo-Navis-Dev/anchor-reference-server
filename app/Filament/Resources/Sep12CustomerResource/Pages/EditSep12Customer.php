<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12CustomerResource\Pages;

use App\Filament\Resources\Sep12CustomerResource;
use App\Filament\Resources\Sep12CustomerResource\Util\Sep12CustomerResourceHelper;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

/**
 *  This class is responsible for editing SEP-12 customer record in the database.
 */
class EditSep12Customer extends EditRecord
{
    protected static string $resource = Sep12CustomerResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /**
         * @var Sep12Customer $customerModel
         */
        $customerModel = $this->getRecord();
        Sep12CustomerResourceHelper::populateCustomerFieldsBeforeFormLoad($data, $customerModel);
        LOG::debug('mutate: ' . json_encode($data));
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /**
         * @var Sep12Customer $customer
         */
        $customer = $this->getRecord();

        LOG::debug('Processing data before save: ' . json_encode($data));
        $prefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;
        $customFields = array_filter($data, function ($key) use ($prefix, $statusSuffix) {
            return str_starts_with($key, $prefix) && !str_ends_with($key, "{$statusSuffix}");
        }, ARRAY_FILTER_USE_KEY);

        foreach ($customFields as $key => $value) {
            $fieldKey = str_replace($prefix, '', $key);
            $providedField = Sep12ProvidedField::where('sep12_customer_id', $customer->id)
                ->where('sep12_field_id', $fieldKey)
                ->first();
            LOG::debug('$providedField: ' . json_encode($providedField));
            $status = null;
            if (isset($data["{$key}{$statusSuffix}"])) {
                $status = $data["{$key}{$statusSuffix}"];
            }
            if ($providedField) {
                $providedField->string_value = $value;
                $providedField->status = $status;
            } else {
                $providedField = new Sep12ProvidedField();
                $providedField->sep12_customer_id = $customer->id;
                $providedField->sep12_field_id = $fieldKey;
                $providedField->string_value = $value;
                $providedField->status = ProvidedCustomerFieldStatus::PROCESSING;
            }
            $providedField->save();
            $providedField->refresh();
        }

        Sep12Field::where('type', 'binary')
            ->get()->each(function ($field) use (&$data, $customer, $prefix, $statusSuffix) {
                $providedField = Sep12ProvidedField::where('sep12_customer_id', $customer->id)
                    ->where('sep12_field_id', $field->id)
                    ->first();
                $statusSubmitKey = "{$prefix}{$field->id}{$statusSuffix}";
                if (isset($data[$statusSubmitKey]) && $providedField != null) {
                    $providedField->status = $data[$statusSubmitKey];
                    $providedField->save();
                }
            });

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
