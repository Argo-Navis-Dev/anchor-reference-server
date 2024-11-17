<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep12CustomerResource\Pages;

use App\Filament\Resources\Sep12CustomerResource;
use App\Filament\Resources\Sep12CustomerResource\Helper\Sep12CustomerResourceHelper;
use App\Models\Sep12Customer;
use App\Models\Sep12Field;
use App\Models\Sep12ProvidedField;
use App\Stellar\Sep12Customer\CustomerIntegration;
use App\Stellar\Sep12Customer\Sep12Helper;
use App\Stellar\Shared\SepHelper;
use ArgoNavis\PhpAnchorSdk\callback\GetCustomerRequest;
use ArgoNavis\PhpAnchorSdk\shared\ProvidedCustomerFieldStatus;
use ArgoNavis\PhpAnchorSdk\Stellar\CallbackHelper;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

use Whoops\Handler\CallbackHandler;

use function json_encode;

/**
 *  This class is responsible for editing SEP-12 customer record in the database.
 */
class EditSep12Customer extends EditRecord
{
    /**
     * @var string $resource The db entity to be edited.
     */
    protected static string $resource = Sep12CustomerResource::class;

    /**
     * Processes the form data model before filling it.
     *
     * @param array $data
     *
     * @return array<array-key, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /**
         * @var Sep12Customer $customerModel
         */
        $customerModel = $this->getRecord();
        Log::debug(
            'Preparing data for edit action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data), 'customer' => json_encode($customerModel)],
        );
        Sep12CustomerResourceHelper::populateCustomerFieldsBeforeFormLoad($data, $customerModel);
        Log::debug(
            'The processed data for edit action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );

        return $data;
    }

    /**
     * Preprocesses the form data model before saving in the database.
     *
     * @param array<array-key, mixed> $data The form data model to be saved.
     *
     * @return array<array-key, mixed> The processed form data model.
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /**
         * @var Sep12Customer $customer
         */
        $customer = $this->getRecord();
        Log::debug(
            'Preparing data for save action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data), 'customer' => json_encode($customer)],
        );

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
                Log::debug(
                    'Binary field status field name.',
                    ['context' => 'sep12_ui', 'status_field_name' => $statusSubmitKey],
                );

                if (isset($data[$statusSubmitKey]) && $providedField != null) {
                    $providedField->status = $data[$statusSubmitKey];
                    $providedField->save();
                }
            });
        Log::debug(
            'The processed data for save action.',
            ['context' => 'sep12_ui', 'data' => json_encode($data)],
        );
//        Send the callback request to the customer's callback URL.
//        $getCustomerRequest = new GetCustomerRequest($customer->account_id, $customer->memo);
//        $customerIntegration = new CustomerIntegration();
//        $sep12CustomerData = $customerIntegration->getCustomer($getCustomerRequest);
//        $signingSeed = config('stellar.server.server_account_signing_key');
//        CallbackHelper::setLogger(Log::getLogger());
//        CallbackHelper::sendCallbackRequest($sep12CustomerData, $signingSeed, $customer->callback_url);

        return $data;
    }

    /**
     * Returns the form header actions.
     *
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
