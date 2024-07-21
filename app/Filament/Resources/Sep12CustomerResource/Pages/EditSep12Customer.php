<?php

namespace App\Filament\Resources\Sep12CustomerResource\Pages;

use App\Filament\Resources\Sep12CustomerResource;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditSep12Customer extends EditRecord
{
    protected static string $resource = Sep12CustomerResource::class;
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /**
         * @var Sep12Customer $customerModel
         */
        $customerModel = $this->getRecord();
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
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {

        /**
         * @var Sep12Customer $customerModel
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
            LOG::debug("fieldKey: " . $fieldKey);
            $providedField = Sep12ProvidedField::where('sep12_customer_id', $customer->id)
                ->where('sep12_field_id', $fieldKey)
                ->first();
            $status = null;
            if (isset($data["{$key}{$statusSuffix}"])) {
                $status = $data["{$key}{$statusSuffix}"];
            }
            if ($providedField) {
                $providedField->string_value = $value;
                $providedField->status = $status;
                $providedField->save();
            }
        }

        Sep12Field::where('type', 'binary')->get()->each(function ($field) use (&$data, $customer, $prefix, $statusSuffix) {
            $providedField = Sep12ProvidedField::where('sep12_customer_id', $customer->id)
                ->where('sep12_field_id', $field->id)
                ->first();
            $statusSubmitKey = "{$prefix}{$field->id}{$statusSuffix}";
            if (isset($data[$statusSubmitKey])) {
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
