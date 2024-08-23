<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\Sep31TransactionResource\Pages;

use App\Filament\Resources\Sep31TransactionResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;

class EditSep31Transaction extends EditRecord
{
    /**
     * @var string $resource The db entity to be edited.
     */
    protected static string $resource = Sep31TransactionResource::class;

    /**
     * Processes the form data model before filling it.
     *
     * @param array<array-key, mixed> $data The form data model to be processed.
     *
     * @return array<array-key, mixed> The processed form data model.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $feeDetails = $data['fee_details'] ?? null;
        if ($feeDetails != null) {
            $data['fee_details'] = json_decode($feeDetails, true);
        }
        $refunds = $data['refunds'] ?? null;
        if ($refunds != null) {
            $data['refunds'] = json_decode($refunds, true);
        }

        $requiredCustomerInfoUpdates = $data['required_customer_info_updates'] ?? null;
        if ($requiredCustomerInfoUpdates != null) {
            $data['required_customer_info_updates'] = json_decode($requiredCustomerInfoUpdates, true);
        }

        $stellarTransactions = $data['stellar_transactions'] ?? null;
        if ($stellarTransactions != null) {
            $data['stellar_transactions'] = json_decode($stellarTransactions, true);
        }

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
