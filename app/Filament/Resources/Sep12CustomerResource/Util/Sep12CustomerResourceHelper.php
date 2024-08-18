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
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

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

    public static function getCustomerCustomFormFields(array $fields, bool $required): array
    {
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;
        $providedFields = [];
        foreach ($fields as $field) {
            $label = __("sep12_lang.label.{$field->key}");
            $descriptionKey = "sep12_lang.label.{$field->key}.description";
            $description =  __($descriptionKey);
            if($description == $descriptionKey) {
                $description = $field->desc;
            }
            $fieldType = $field->type;
            $name = "{$customFieldPrefix}{$field->id}";

            $hasStatus = !isset(Sep12CustomerResource::KYC_FIELD_WITHOUT_STATUS[$field->key]);
            if ($fieldType == 'string') {
                if ($field->choices != null) {
                    $providedFields[] = self::createDynamicSelectField($field, $hasStatus, $description, $required);
                } else {
                    $providedFields[] = TextInput::make(name: $name)
                        ->required($required)
                        ->helperText($description)
                        ->label($label);
                }
            }
            if ($fieldType == 'binary') {
                $providedFields[] = self::getBinaryFieldComponent($field->id, $label, $description);
            }
            $statusFieldName = "{$customFieldPrefix}{$field->id}{$statusSuffix}";
            if ($hasStatus) {
                $requiresVerification = $field->requires_verification;
                $statusField = self::createProvidedFieldStatusComp($statusFieldName, $requiresVerification);
                $providedFields[] = $statusField;
            }
        }
        return $providedFields;
    }

    private static function createDynamicSelectField(Sep12Field $field,
        bool $hasStatusField,
        string $description,
        bool $required): Select
    {
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $name = "{$customFieldPrefix}{$field->id}";
        $choices = explode(",", $field->choices);
        $options = [];
        foreach ($choices as $choice) {
            $options[$choice] = __("sep12_lang.label.{$field->key}.{$choice}");
        }
        $component = Select::make($name)
            ->label(__("sep12_lang.label.{$field->key}"))
            ->helperText($description)
            ->columnSpan(1)
            ->required($required)
            ->options($options);
        if (!$hasStatusField) {
            $component->columnSpan(2);
        }
        return $component;
    }

    private static function getBinaryFieldComponent(
        string $fieldID,
        string $label,
        string $description
    ): Placeholder {
        return Placeholder::make('Image')
            ->hidden(fn($record) => $record == null)
            ->label($label)
            ->helperText($description)
            ->content(function ($record) use ($fieldID): HtmlString {
                $id = $record != null ? $record->id : null;
                $src = '/customer/' . $id . '/binary-field/' . $fieldID;
                return new HtmlString("<img src= '" . $src . "')>");
            });
    }

    private static function createProvidedFieldStatusComp(string $name, bool $requiresVerification): Field {
        $option = [
            ProvidedCustomerFieldStatus::ACCEPTED => __('sep12_lang.label.field.status.accepted'),
            ProvidedCustomerFieldStatus::PROCESSING => __('sep12_lang.label.field.status.processing'),
            ProvidedCustomerFieldStatus::REJECTED => __('sep12_lang.label.field.status.rejected'),
        ];
        if($requiresVerification) {
            $option[ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED] = __('sep12_lang.label.field.status.verification_required');
        }
        return Select::make($name)
            ->label(__('shared_lang.label.status'))
            ->afterStateUpdated(function (Set $set, Get $get, $state, Sep12Customer $customer) {
                Sep12CustomerResourceHelper::onCustomerFieldStatusChanged($state, $set, $get, $customer);
            })
            ->live()
            ->default(CustomerStatus::PROCESSING)
            ->options($option);
    }

}