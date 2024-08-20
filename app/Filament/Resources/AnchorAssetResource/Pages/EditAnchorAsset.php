<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\AnchorAssetResource\Pages;

use App\Filament\Resources\AnchorAssetResource;
use App\Filament\Resources\AnchorAssetResource\Helper\AnchorAssetResourceHelper;
use App\Models\AnchorAsset;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

/**
 * This class is responsible for editing an anchor asset record in the database.
 */
class EditAnchorAsset extends EditRecord
{
    /**
     * @var string $resource The db entity to be edited.
     */
    protected static string $resource = AnchorAssetResource::class;

    /**
     * Processes the form data model before filling it.
     *
     * @param array<array-key, mixed> $data The form data model.
     * @return array<array-key, mixed> $data The mutated form data model.
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /**
         * @var array<array-key, mixed> $data The form data model.
         * @var AnchorAsset $anchorAsset The DB entity.
         */
        $anchorAsset = $this->getRecord();
        AnchorAssetResourceHelper::populateSep31InfoBeforeFormLoad($data, $anchorAsset);
        AnchorAssetResourceHelper::populateSep38InfoBeforeFormLoad($data, $anchorAsset);

        $sep06WithdrawMethodsStr = $data['sep06_withdraw_methods'] ?? null;
        if ($sep06WithdrawMethodsStr != null) {
            $sep06WithdrawMethods = array_map('trim', explode(',', $sep06WithdrawMethodsStr));
            $data['sep06_withdraw_methods'] = $sep06WithdrawMethods;
        }

        $sep06DepositMethodsStr = $data['sep06_deposit_methods'] ?? null;
        if ($sep06DepositMethodsStr != null) {
            $sep06DepositMethods = array_map('trim', explode(',', $sep06DepositMethodsStr));
            $data['sep06_deposit_methods'] = $sep06DepositMethods;
        }
        return $data;
    }

    /**
     * Prepares the form data model to be saved.
     *
     * @param array<array-key, mixed> $data The form data model.
     * @return array<array-key, mixed> The mutated model before saving it.
     * @throws \JsonException
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AnchorAssetResourceHelper::mutateFormDataBeforeSave($data);
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
