<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12CustomerResource\Helper;

use App\Filament\Resources\Sep12CustomerResource;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;
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

/**
 *  Helper for SEP-12 customer CRUD operations.
 */
class Sep12CustomerResourceHelper
{

    /**
     * Populates the customer provided fields into the form data model.
     *
     * @param array<array-key, mixed> $data The form data model.
     * @param Sep12Customer $customerModel The customer being edited.
     *
     * @return void
     */
    public static function populateCustomerFieldsBeforeFormLoad(
        array &$data,
        Sep12Customer $customerModel,
    ): void {
        $fields = Sep12Field::all();
        $fieldIDToBean = $fields->keyBy('id')->all();
        $prefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;

        $providedFields = Sep12ProvidedField::where('sep12_customer_id', $customerModel->id)->get();
        $providedFields->each(function ($providedField) use (
            &$data,
            $fieldIDToBean,
            $prefix,
            $statusSuffix
        ) {
            $field = $fieldIDToBean[$providedField->sep12_field_id];
            $name = "{$prefix}{$field->id}";
            $data[$name] = $providedField->string_value;
            $data["{$name}{$statusSuffix}"] = $providedField->status;
        });
    }

    /**
     * Event listener when customer status value changes.
     *
     * @param string $newState The newly selected state.
     * @param callable $set Form data model setter.
     * @param Sep12Customer $customer The customer being edited.
     *
     * @return void
     */
    public static function onCustomerStatusChanged(
        string $newState,
        callable $set,
        Sep12Customer $customer
    ): void {
        if ($newState == CustomerStatus::ACCEPTED) {
            self::updateAllFieldStatus(
                ProvidedCustomerFieldStatus::ACCEPTED,
                $set,
                $customer
            );
        }
    }

    /**
     * Updates all customer fields status to the passed value.
     *
     * @param string $newFieldStatus The new status value.
     * @param callable $set Form data model setter.
     * @param Sep12Customer $customer The customer being edited.
     *
     * @return void
     */
    private static function updateAllFieldStatus(
        string $newFieldStatus,
        callable $set,
        Sep12Customer $customer
    ): void {
        $fieldNames = self::getAllFieldStatusNames($customer);
        foreach ($fieldNames as $statusFieldName) {
            $set($statusFieldName, $newFieldStatus);
        }
    }

    /**
     * Gathers the name of all field specific status components.
     *
     * @param Sep12Customer $customer
     *
     * @return array<string> The list of status field names represented in the form data model
     */
    private static function getAllFieldStatusNames(
        Sep12Customer $customer
    ): array {
        $type = $customer->type;
        $sep12FieldsForType = Sep12Helper::getSep12FieldsForCustomerType($type);
        $allFields = [];
        $optionalFields = $sep12FieldsForType['optional'];
        if ($optionalFields != null) {
            $allFields = array_merge($allFields, $optionalFields);
        }

        $requiredFields = $sep12FieldsForType['required'];
        if ($requiredFields != null) {
            $allFields = array_merge($allFields, $requiredFields);
        }

        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;
        $fieldNames = [];
        foreach ($allFields as $field) {
            $hasStatus = !isset(Sep12CustomerResource::KYC_FIELD_WITHOUT_STATUS[$field->key]);
            if ($hasStatus) {
                $fieldNames[] = "{$customFieldPrefix}{$field->id}{$statusSuffix}";
            }
        }

        return $fieldNames;
    }

    /**
     * Creates the customer custom fields form components.
     *
     * @param array<Sep12Field> $fields The form components.
     * @param bool $required Flag indicating if the fields are required.
     *
     * @return array<mixed> The form components.
     */
    public static function createCustomerCustomFormFields(array $fields, bool $required): array
    {
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $statusSuffix = Sep12CustomerResource::CUSTOM_STATUS_FIELD_SUFFIX;
        $providedFields = [];
        foreach ($fields as $field) {
            $label = __("sep12_lang.label.{$field->key}");
            $descriptionKey = "sep12_lang.label.{$field->key}.description";
            $description = __($descriptionKey);
            if ($description == $descriptionKey) {
                $description = $field->desc;
            }
            $fieldType = $field->type;
            $name = "{$customFieldPrefix}{$field->id}";

            $hasStatus = !isset(Sep12CustomerResource::KYC_FIELD_WITHOUT_STATUS[$field->key]);
            if ($fieldType == 'string') {
                if ($field->choices != null) {
                    $providedFields[] = self::createGenericSelectFormComponent($field, $hasStatus, $description, $required);
                } else {
                    $providedFields[] = TextInput::make(name: $name)
                        ->required($required)
                        ->helperText($description)
                        ->label($label);
                }
            }
            if ($fieldType == 'binary') {
                $providedFields[] = self::createGenericBinaryFormComponent($field->id, $label, $description);
            }
            $statusFieldName = "{$customFieldPrefix}{$field->id}{$statusSuffix}";
            if ($hasStatus) {
                $requiresVerification = (bool)$field->requires_verification;
                $statusField = self::createGenericFieldStatusFormComponent($statusFieldName, $requiresVerification);
                $providedFields[] = $statusField;
            }
        }

        return $providedFields;
    }

    /**
     * Creates a generic select form component by the passed parameters.
     *
     * @param Sep12Field $field The represented field.
     * @param bool $hasStatusField The flag indicating whether the field has status field associated.
     * @param string $description The field localized description.
     * @param bool $required Flag indicating whether the field is required.
     *
     * @return Select The form component.
     */
    private static function createGenericSelectFormComponent(
        Sep12Field $field,
        bool $hasStatusField,
        string $description,
        bool $required
    ): Select {
        $customFieldPrefix = Sep12CustomerResource::CUSTOM_FIELD_PREFIX;
        $name = "{$customFieldPrefix}{$field->id}";
        $choices = explode(",", $field->choices);
        $options = [];
        foreach ($choices as $choice) {
            $convertedChoice = trim($choice);
            $convertedChoice = strtolower($convertedChoice);
            $convertedChoice = preg_replace('/\s+/', '_', $convertedChoice);
            $options[$choice] = __("sep12_lang.label.{$field->key}.{$convertedChoice}");
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

    /**
     * Creates a generic binary form component for displaying images.
     *
     * @param int $fieldID The field id to be shown.
     * @param string $label The field label.
     * @param string $description The field localized description.
     *
     * @return Placeholder The image form component wrapper containing the image.
     */
    private static function createGenericBinaryFormComponent(
        int $fieldID,
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

    /**
     * Creates a generic field status form component.
     *
     * @param string $name The name of the form component
     * @param bool $requiresVerification If true the corresponding status options must be selectable.
     *
     * @return Field The created form component
     */
    private static function createGenericFieldStatusFormComponent(string $name, bool $requiresVerification): Field
    {
        $option = [
            ProvidedCustomerFieldStatus::ACCEPTED => __('sep12_lang.label.field.status.accepted'),
            ProvidedCustomerFieldStatus::PROCESSING => __('sep12_lang.label.field.status.processing'),
            ProvidedCustomerFieldStatus::REJECTED => __('sep12_lang.label.field.status.rejected'),
        ];
        if ($requiresVerification) {
            $option[ProvidedCustomerFieldStatus::VERIFICATION_REQUIRED] =
                __('sep12_lang.label.field.status.verification_required');
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

    /**
     * Listener for customer provided field status component change event.
     *
     * @param string $newState The newly selected state.
     * @param callable $set The form data model getter.
     * @param callable $get The form data model setter.
     * @param Sep12Customer $customer The customer being edited.
     *
     * @return void
     */
    public static function onCustomerFieldStatusChanged(
        string $newState,
        callable $set,
        callable $get,
        Sep12Customer $customer
    ): void {
        if ($newState != ProvidedCustomerFieldStatus::ACCEPTED) {
            $set('status', CustomerStatus::PROCESSING);
        } else {
            $fieldStatusNames = self::getAllFieldStatusNames();
            $allFieldStatusesAccepted = true;
            foreach ($fieldStatusNames as $fieldStatusName) {
                $fieldStatusValue = $get($fieldStatusName);
                if ($fieldStatusValue != ProvidedCustomerFieldStatus::ACCEPTED) {
                    $allFieldStatusesAccepted = false;
                }
            }
            if ($allFieldStatusesAccepted) {
                $set('status', CustomerStatus::ACCEPTED);
            }
        }
    }
}
