<?php

namespace App\Filament\Resources\AnchorAssetResource\Util;

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.
use App\Models\AnchorAsset;
use App\Stellar\Sep31CrossBorder\Sep31Helper;
use App\Stellar\Sep38Quote\Sep38Helper;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use Illuminate\Support\Facades\Log;

class AnchorAssetResourceHelper
{
    public static function populateSep31InfoBeforeFormLoad(array &$data, AnchorAsset $anchorAsset): void {
        try {
            //TODO check this conversion
            if($anchorAsset->sep31_info != null) {
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
            LOG::error($e);
        }
    }

    public static function populateSep38InfoBeforeFormLoad(array &$data, AnchorAsset $anchorAsset): void {
        try {
            $sep38AssetInfo = Sep38Helper::sep38AssetInfoFromAnchorAsset($anchorAsset);

            $sellDeliveryMethods = array();
            if($sep38AssetInfo->sellDeliveryMethods != null) {
                foreach ($sep38AssetInfo->sellDeliveryMethods as $value) {
                    $method = array("name" => $value->name, "description" => $value->description);
                    $sellDeliveryMethods[] = $method;
                }
            }
            $data['sep38_cfg_sell_delivery_methods'] = $sellDeliveryMethods;

            $buyDeliveryMethods = array();
            if($sep38AssetInfo->buyDeliveryMethods != null) {
                foreach ($sep38AssetInfo->buyDeliveryMethods as $value) {
                    $method = array("name" => $value->name, "description" => $value->description);
                    $buyDeliveryMethods[] = $method;
                }
            }
            $data['sep38_cfg_buy_delivery_methods'] = $buyDeliveryMethods;
            $data['sep38_cfg_country_codes'] = $sep38AssetInfo->countryCodes;

            //$data['sep38_cfg_sell_delivery_methods']
        } catch (InvalidAsset $e) {
            LOG::error($e);
        }
    }
}