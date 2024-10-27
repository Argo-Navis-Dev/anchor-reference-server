<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Filament\Resources\AnchorAssetResource\Helper;

use App\Models\AnchorAsset;
use App\Stellar\Sep31CrossBorder\Sep31Helper;
use App\Stellar\Sep38Quote\Sep38Helper;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep12Type;
use ArgoNavis\PhpAnchorSdk\shared\Sep31AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep38AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep38DeliveryMethod;
use Illuminate\Support\Facades\Log;

use function json_encode;

/**
 * Helper for Anchor asset CRUD operations.
 */
class AnchorAssetResourceHelper
{
    /**
     * Preprocesses the form data model before saving in the database.
     *
     * @param array<array-key, mixed> $data The form data model.
     *
     * @return array<array-key, mixed> The processed form data model.
     *
     */
    public static function mutateFormDataBeforeSave(array $data): array
    {
        try {
            Log::debug(
                'Processing anchor asset data for save action.',
                ['context' => 'anchor_asset_ui', 'data' => json_encode($data)],
            );

            $sep31InfoJson = self::processSep31InfoBeforeSave($data);
            $sep38InfoJson = self::processSep38InfoBeforeSave($data);
            $data['sep31_info'] = $sep31InfoJson;
            $data['sep38_info'] = $sep38InfoJson;

            $sep06WithdrawMethods = $data['sep06_withdraw_methods'] ?? null;
            if ($sep06WithdrawMethods != null) {
                $sep06WithdrawMethodsStr = implode(",", $sep06WithdrawMethods);
                $data['sep06_withdraw_methods'] = $sep06WithdrawMethodsStr;
            } else {
                $data['sep06_withdraw_methods'] = null;
            }

            $sep06DepositMethods = $data['sep06_deposit_methods'] ?? null;
            if ($sep06DepositMethods != null) {
                $sep06DepositMethodsStr = implode(",", $sep06DepositMethods);
                $data['sep06_deposit_methods'] = $sep06DepositMethodsStr;
            } else {
                $data['sep06_deposit_methods'] = null;
            }
        } catch (InvalidAsset | \JsonException $e) {
            Log::error(
                'Failed to process data for the save action.',
                ['context' => 'anchor_asset_ui', 'error' => $e->getMessage(), 'exception' => $e],
            );
        }
        unset($data['sep31_cfg_quotes_supported']);
        unset($data['sep31_cfg_sep12_sender_types']);
        unset($data['sep31_cfg_sep12_receiver_types']);
        unset($data['sep38_cfg_country_codes']);
        unset($data['sep38_cfg_decimals']);
        unset($data['sep38_cfg_sell_delivery_methods']);
        unset($data['sep38_cfg_buy_delivery_methods']);
        Log::debug(
            'The processed anchor asset data for save action.',
            ['context' => 'anchor_asset_ui', 'data' => json_encode($data)],
        );

        return $data;
    }

    /**
     * Processed the SPE-31 info JSON before saving in DB.
     *
     * @param array<array-key, mixed | string> $data
     * @return string
     * @throws InvalidAsset
     * @throws \JsonException
     */
    private static function processSep31InfoBeforeSave(array $data): string
    {
        $schema = $data['schema'] ?? '';
        $code = $data['code'] ?? '';
        $issuer = $data['issuer'] ?? null;
        $asset = new IdentificationFormatAsset(
            schema: $schema,
            code: $code,
            issuer: $issuer
        );

        $sep31CfgSep12SenderTypes = $data['sep31_cfg_sep12_sender_types'] ?? null;
        $senderTypes = [];
        if ($sep31CfgSep12SenderTypes != null) {
            foreach ($sep31CfgSep12SenderTypes as $type) {
                $senderTypes[] = new Sep12Type(
                    name: $type['name'],
                    description: $type['description'],
                );
            }
        }

        $sep31CfgSep12ReceiverTypes = $data['sep31_cfg_sep12_receiver_types'] ?? null;
        $receiverTypes = [];
        if ($sep31CfgSep12ReceiverTypes != null) {
            foreach ($sep31CfgSep12ReceiverTypes as $type) {
                $receiverTypes[] = new Sep12Type(
                    name: $type['name'],
                    description: $type['description'],
                );
            }
        }

        $sep31Asset = new Sep31AssetInfo(
            asset: $asset,
            sep12SenderTypes: $senderTypes,
            sep12ReceiverTypes: $receiverTypes,
            quotesSupported: $data['sep31_cfg_quotes_supported'] ?? null,
            quotesRequired: $data['sep31_cfg_quotes_required'] ?? null,
        );
        Log::debug(
            'The parsed SEP-31 asset info for save action.',
            ['context' => 'anchor_asset_ui', 'sep31_asset_info' => json_encode($sep31Asset),
                'data' => json_encode($data),
            ],
        );

        return json_encode($sep31Asset->toJson(), JSON_THROW_ON_ERROR);
    }

    /**
     *  Processed SEP-38 info before save.
     *
     * @param array<array-key, mixed> $data The form data model.
     *
     * @throws InvalidAsset
     */
    private static function processSep38InfoBeforeSave(array $data): string
    {
        $asset = new IdentificationFormatAsset(
            schema: $data['schema'],
            code: $data['code'],
            issuer: $data['issuer'],
        );

        $sellDeliveryMethods = $data['sep38_cfg_sell_delivery_methods'] ?? null;
        $sellMethodsByType = [];
        if ($sellDeliveryMethods != null) {
            foreach ($sellDeliveryMethods as $method) {
                $sellMethodsByType[] = new Sep38DeliveryMethod(
                    name: $method['name'],
                    description: $method['description'],
                );
            }
        }

        $buyDeliveryMethods = $data['sep38_cfg_buy_delivery_methods'] ?? null;
        $buyMethodsByType = [];
        if ($buyDeliveryMethods != null) {
            foreach ($buyDeliveryMethods as $method) {
                $buyMethodsByType[] = new Sep38DeliveryMethod(
                    name: $method['name'],
                    description: $method['description'],
                );
            }
        }

        $sep38AssetInfo = new Sep38AssetInfo(
            asset: $asset,
            sellDeliveryMethods: $sellMethodsByType,
            buyDeliveryMethods: $buyMethodsByType,
            countryCodes: $data['sep38_cfg_country_codes'] ?? null,
        );

        $sep38AssetInfoJson = $sep38AssetInfo->toJson();

        $result = array(
            'sell_delivery_methods' => $sep38AssetInfoJson['sell_delivery_methods'] ?? [],
            'buy_delivery_methods' => $sep38AssetInfoJson['buy_delivery_methods'] ?? [],
            'country_codes' => $sep38AssetInfoJson['country_codes'] ?? []
        );
        Log::debug(
            'The parsed SEP-38 asset info for save action.',
            ['context' => 'anchor_asset_ui', 'sep31_asset_info' => json_encode($result),
                'data' => json_encode($data),
            ],
        );

        return json_encode($result);
    }

    /**
     * Populates the SEP-31 info before loading the form.
     *
     * @param array<array-key, mixed> $data The form data model.
     *
     * @param AnchorAsset $anchorAsset The DB entity to be loaded.
     *
     * @return void
     */
    public static function populateSep31InfoBeforeFormLoad(array &$data, AnchorAsset $anchorAsset): void
    {
        try {
            if ($anchorAsset->sep31_info != null) {
                $sep31AssetInfo = Sep31Helper::sep31AssetInfoFromAnchorAsset($anchorAsset);

                $senderTypes = array();
                foreach ($sep31AssetInfo->sep12SenderTypes as $value) {
                    $type = array("name" => $value->name, "description" => $value->description);
                    $senderTypes[] = $type;
                }
                $data['sep31_cfg_sep12_sender_types'] = $senderTypes;

                $receiverTypes = array();
                foreach ($sep31AssetInfo->sep12ReceiverTypes as $value) {
                    $type = array("name" => $value->name, "description" => $value->description);
                    $receiverTypes[] = $type;
                }
                $data['sep31_cfg_sep12_receiver_types'] = $receiverTypes;

                $data['sep31_cfg_quotes_supported'] = $sep31AssetInfo->quotesSupported;
                $data['sep31_cfg_quotes_required'] = $sep31AssetInfo->quotesRequired;
            }
        } catch (InvalidAsset $e) {
            Log::error(
                'Invalid asset, failed to populate the SEP-31 info for edit action.',
                ['context' => 'anchor_asset_ui', 'error' => $e->getMessage(), 'exception' => $e],
            );
        }
    }

    /**
     * Populates the SEP-38 info before loading the form.
     *
     * @param array<array-key, mixed> $data
     * @param AnchorAsset $anchorAsset The DB entity to be loaded.
     * @return void
     */
    public static function populateSep38InfoBeforeFormLoad(array &$data, AnchorAsset $anchorAsset): void
    {
        try {
            $sep38AssetInfo = Sep38Helper::sep38AssetInfoFromAnchorAsset($anchorAsset);
            $sellDeliveryMethods = array();
            if ($sep38AssetInfo->sellDeliveryMethods != null) {
                foreach ($sep38AssetInfo->sellDeliveryMethods as $value) {
                    $method = array("name" => $value->name, "description" => $value->description);
                    $sellDeliveryMethods[] = $method;
                }
            }
            $data['sep38_cfg_sell_delivery_methods'] = $sellDeliveryMethods;

            $buyDeliveryMethods = array();
            if ($sep38AssetInfo->buyDeliveryMethods != null) {
                foreach ($sep38AssetInfo->buyDeliveryMethods as $value) {
                    $method = array("name" => $value->name, "description" => $value->description);
                    $buyDeliveryMethods[] = $method;
                }
            }
            $data['sep38_cfg_buy_delivery_methods'] = $buyDeliveryMethods;
            $data['sep38_cfg_country_codes'] = $sep38AssetInfo->countryCodes;
        } catch (InvalidAsset $e) {
            Log::error(
                'Invalid asset, failed to populate the SEP-38 info for edit action.',
                ['context' => 'anchor_asset_ui', 'error' => $e->getMessage(), 'exception' => $e],
            );
        }
    }
}
