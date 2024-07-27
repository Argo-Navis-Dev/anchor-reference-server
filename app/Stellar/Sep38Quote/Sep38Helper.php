<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Stellar\Sep38Quote;

use App\Models\AnchorAsset;
use App\Models\Sep38ExchangeQuote;
use App\Models\Sep38Rate;
use ArgoNavis\PhpAnchorSdk\callback\Sep38PriceRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep38PricesRequest;
use ArgoNavis\PhpAnchorSdk\callback\Sep38QuoteRequest;
use ArgoNavis\PhpAnchorSdk\exception\AnchorFailure;
use ArgoNavis\PhpAnchorSdk\exception\InvalidAsset;
use ArgoNavis\PhpAnchorSdk\exception\QuoteNotFoundForId;
use ArgoNavis\PhpAnchorSdk\shared\IdentificationFormatAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep38AssetInfo;
use ArgoNavis\PhpAnchorSdk\shared\Sep38BuyAsset;
use ArgoNavis\PhpAnchorSdk\shared\Sep38DeliveryMethod;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfo;
use ArgoNavis\PhpAnchorSdk\shared\TransactionFeeInfoDetail;
use ArgoNavis\PhpAnchorSdk\shared\Sep38Price;
use ArgoNavis\PhpAnchorSdk\shared\Sep38Quote;
use DateInterval;
use DateTime;
use DateTimeInterface;
use Exception;
use Illuminate\Support\Facades\Log;
use Throwable;

class Sep38Helper
{

    /**
     * @return array<Sep38AssetInfo> the assets having sep38 support enabled.
     */
    public static function getSupportedAssets(): array {
        /**
         * @var array<Sep38AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep38Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            return $result;
        }
        foreach ($assets as $asset) {
            try {
                $result[] = self::sep38AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error('invalid anchor_asset (id: '. $asset->id . ') in db: ' . $iA->getMessage());
            }
        }

        return $result;
    }

    /**
     * @param Sep38PricesRequest $request the request data
     * @return array<Sep38BuyAsset> the result containing the prices
     * @throws AnchorFailure
     */
    public static function getPrices(Sep38PricesRequest $request) : array {
        /**
         * @var array<Sep38BuyAsset> $result
         */
        $result = [];
        $rates = Sep38Rate::whereSellAsset($request->sellAsset->getStringRepresentation())->get();
        if ($rates === null || count($rates) === 0) {
            return $result;
        }

        $sellAmount = floatval($request->sellAmount);

        foreach ($rates as $rate) {
            try {
                $fee = ($rate->fee_percent / 100) * $sellAmount;
                $buyAmount = ($sellAmount - $fee) / $rate->rate;
                $totalPrice = $sellAmount / $buyAmount;
                $buyAsset = IdentificationFormatAsset::fromString($rate->buy_asset);
                $decimals = self::significantDecimalsForAsset($buyAsset);
                $result[] = new Sep38BuyAsset(
                    asset: $buyAsset,
                    price: strval($totalPrice),
                    decimals: $decimals,
                );
            } catch (InvalidAsset $iA) {
                Log::error('invalid anchor_asset (id: '. $rate->id . ') in db: ' . $iA->getMessage());
            }
        }

        return $result;

    }

    /**
     * @param Sep38PriceRequest $request the request data
     * @return Sep38Price the result containing the price
     * @throws AnchorFailure
     */
    public static function getPrice(Sep38PriceRequest $request) : Sep38Price {

        $rate = Sep38Rate::where('sell_asset', $request->sellAsset->getStringRepresentation())
            ->where('buy_asset', $request->buyAsset->getStringRepresentation())->first();
        if ($rate === null) {
            throw new AnchorFailure('no rate available');
        }

        $sellAssetDecimals = self::significantDecimalsForAsset($request->sellAsset);
        $buyAssetDecimals = self::significantDecimalsForAsset($request->buyAsset);

        if ($request->sellAmount !== null) {

            $sellAmount = floatval($request->sellAmount);
            $fee = ($rate->fee_percent / 100) * $sellAmount;
            $sep38Fee = new TransactionFeeInfo(strval(round($fee, $sellAssetDecimals)), $request->sellAsset);
            $buyAmount = ($sellAmount - $fee) / $rate->rate;
            $totalPrice = $sellAmount / $buyAmount;

            return new Sep38Price(
                totalPrice: strval($totalPrice),
                price: strval($rate->rate),
                sellAmount: strval($sellAmount),
                buyAmount: strval(round($buyAmount, $buyAssetDecimals)),
                fee: $sep38Fee,
            );
        } else if ($request->buyAmount !== null) {
            $buyAmount = floatval($request->buyAmount);
            $fee = ($rate->fee_percent / 100) * $buyAmount;
            $sellAmount = $rate->rate * ($buyAmount + $fee);
            $sep38Fee = new TransactionFeeInfo(strval(round($fee, $buyAssetDecimals)), $request->buyAsset);
            $totalPrice = $sellAmount / $buyAmount;

            return new Sep38Price(
                totalPrice: strval($totalPrice),
                price: strval($rate->rate),
                sellAmount: strval(round($sellAmount, $sellAssetDecimals)),
                buyAmount: strval($buyAmount),
                fee: $sep38Fee,
            );
        }

        throw new AnchorFailure('either sell_amount or buy_amount must be provided');
    }

    /**
     * @param Sep38QuoteRequest $request the request containing the data for the quote to be created and saved.
     * @return Sep38Quote the created and saved quote
     *
     * @throws AnchorFailure if any error occurs or the quote can not be provided.
     */
    public static function addQuote(Sep38QuoteRequest $request) : Sep38Quote {
        $now = new DateTime();
        $hours = 24;
        $expiresAt = (clone $now)->add(new DateInterval("PT{$hours}H"));
        if ($request->expireAfter !== null && $request->expireAfter > $expiresAt) {
            throw new AnchorFailure(
                'can not provide quote after ' .
                $expiresAt->format(DateTimeInterface::ATOM)
            );
        }

        $priceRequest = new Sep38PriceRequest(
            context: $request->context,
            sellAsset: $request->sellAsset,
            buyAsset: $request->buyAsset,
            sellAmount: $request->sellAmount,
            buyAmount: $request->buyAmount,
            sellDeliveryMethod: $request->sellDeliveryMethod,
            buyDeliveryMethod: $request->buyDeliveryMethod,
            countryCode: $request->countryCode,
            accountId: $request->accountId,
            accountMemo: $request->accountMemo,
        );

        $price = self::getPrice($priceRequest);

        $exchangeQuote = new Sep38ExchangeQuote();
        $exchangeQuote->context = $request->context;
        $exchangeQuote->expires_at = $expiresAt->format(DateTimeInterface::ATOM);
        $exchangeQuote->price = $price->price;
        $exchangeQuote->total_price = $price->totalPrice;
        $exchangeQuote->sell_asset = $request->sellAsset->getStringRepresentation();
        $exchangeQuote->sell_amount = $price->sellAmount;
        $exchangeQuote->sell_delivery_method = $request->sellDeliveryMethod;
        $exchangeQuote->buy_asset = $request->buyAsset->getStringRepresentation();
        $exchangeQuote->buy_amount = $price->buyAmount;
        $exchangeQuote->buy_delivery_method = $request->buyDeliveryMethod;
        $exchangeQuote->fee = json_encode($price->fee->toJson());
        $exchangeQuote->account_id = $request->accountId;
        $exchangeQuote->account_memo = $request->accountMemo;
        $exchangeQuote->save();
        $exchangeQuote->refresh();

        try {
            return self::sep38QuoteFromExchangeQuote($exchangeQuote);
        } catch (Throwable $t) {
            Log::error('error creating quote: ' . $t->getMessage());
            throw new AnchorFailure('error creating quote');
        }
    }

    /**
     * @throws AnchorFailure
     * @throws QuoteNotFoundForId
     */
    public static function getQuoteById(string $id, string $accountId, ?string $accountMemo = null) : Sep38Quote {
        $exchangeQuote = Sep38ExchangeQuote::where('id', $id)
            ->where('account_id', $accountId)
            ->where('account_memo', $accountMemo)
            ->first();
        if ($exchangeQuote === null) {
            throw new QuoteNotFoundForId($id);
        }
        try {
            return self::sep38QuoteFromExchangeQuote($exchangeQuote);
        } catch (Throwable $t) {
            Log::error('error reading quote: ' . $t->getMessage());
            throw new AnchorFailure('error reading quote');
        }
    }

    /**
     * @throws AnchorFailure
     */
    private static function significantDecimalsForAsset(IdentificationFormatAsset $asset) : int {
        $dbAsset = AnchorAsset::where('code', $asset->getCode())
            ->where('issuer', $asset->getIssuer())
            ->where('schema', $asset->getSchema())->first();
        if ($dbAsset === null) {
            throw new AnchorFailure('asset not found ' . $asset->getStringRepresentation());
        }
        return $dbAsset->significant_decimals;
    }
    /**
     * @throws InvalidAsset
     */
    public static function sep38AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset): Sep38AssetInfo {
        $formattedAsset = new IdentificationFormatAsset
        (
            $anchorAsset->schema,
            $anchorAsset->code,
            $anchorAsset->issuer,
        );
        $result = new Sep38AssetInfo($formattedAsset);

        $sep38Info = $anchorAsset->sep38_info;
        if ($sep38Info !== null) {
            $jsonData = json_decode($sep38Info, true);
            if ($jsonData === null) {
                return $result;
            }
            if (isset($jsonData['sell_delivery_methods']) && is_array($jsonData['sell_delivery_methods'])) {
                $sellDeliveryMethods = [];
                foreach ($jsonData['sell_delivery_methods'] as $method) {
                    $sellDeliveryMethod = self::sep38DeliveryMethodFromJson($method);
                    if ($sellDeliveryMethod !== null) {
                        $sellDeliveryMethods[] = $sellDeliveryMethod;
                    }
                }
                $result->sellDeliveryMethods = $sellDeliveryMethods;
            }

            if (isset($jsonData['buy_delivery_methods']) && is_array($jsonData['buy_delivery_methods'])) {
                $buyDeliveryMethods = [];
                foreach ($jsonData['buy_delivery_methods'] as $method) {
                    $buyDeliveryMethod = self::sep38DeliveryMethodFromJson($method);
                    if ($buyDeliveryMethod !== null) {
                        $buyDeliveryMethods[] = $buyDeliveryMethod;
                    }
                }
                $result->buyDeliveryMethods = $buyDeliveryMethods;
            }

            if (isset($jsonData['country_codes']) && is_array($jsonData['country_codes'])) {
                $countryCodes = [];
                foreach ($jsonData['country_codes'] as $code) {
                    if(is_string($code)) {
                        $countryCodes[] = $code;
                    }
                }
                $result->countryCodes = $countryCodes;
            }
        }
        return $result;
    }

    private static function sep38DeliveryMethodFromJson(array $method) : ?Sep38DeliveryMethod {
        $name = null;
        $desc = null;
        if (isset($method['name']) && is_string($method['name'])) {
            $name = $method['name'];
        }
        if (isset($method['description']) && is_string($method['description'])) {
            $desc = $method['description'];
        }
        if ($name !== null && $desc != null) {
            return new Sep38DeliveryMethod($name, $desc);
        }
        return null;
    }


    /**
     * @throws Exception
     */
    private static function sep38QuoteFromExchangeQuote(Sep38ExchangeQuote $exchangeQuote) : Sep38Quote {
        $feeJsonData = json_decode($exchangeQuote->fee, true);
        if ($feeJsonData === null) {
            throw new Exception('invalid fee data in quote ' . $exchangeQuote->id);
        }

        if (!isset($feeJsonData['total']) || !isset($feeJsonData['asset'])) {
            throw new Exception('invalid fee data in quote '  . $exchangeQuote->id);
        }

        $sep38Fee = new TransactionFeeInfo(
            total: $feeJsonData['total'],
            asset: IdentificationFormatAsset::fromString($feeJsonData['asset']),
        );

        if (isset($feeJsonData['details']) && is_array($feeJsonData['details'])) {
            /**
             * @var array<TransactionFeeInfoDetail> $feeDetails
             */
            $feeDetails = [];
            foreach ($feeJsonData['details'] as $detail) {
                if (!isset($detail['name']) || !isset($detail['amount'])) {
                    throw new Exception('invalid fee data in quote '  . $exchangeQuote->id);
                }
                $feeDetail = new TransactionFeeInfoDetail(
                    name:$detail['name'],
                    amount:$detail['amount'],
                );
                if (isset($detail['description']) && is_string($detail['description'])) {
                    $feeDetail->description = $detail['description'];
                }
                $feeDetails[] = $feeDetail;
            }
            $sep38Fee->details = $feeDetails;
        }
        return new Sep38Quote(
            id: strval($exchangeQuote->id),
            expiresAt: DateTime::createFromFormat(DATE_ATOM, $exchangeQuote->expires_at),
            totalPrice: $exchangeQuote->total_price,
            price: $exchangeQuote->price,
            sellAsset: IdentificationFormatAsset::fromString($exchangeQuote->sell_asset),
            sellAmount: $exchangeQuote->sell_amount,
            buyAsset: IdentificationFormatAsset::fromString($exchangeQuote->buy_asset),
            buyAmount: $exchangeQuote->buy_amount,
            fee: $sep38Fee,
        );
    }
}
