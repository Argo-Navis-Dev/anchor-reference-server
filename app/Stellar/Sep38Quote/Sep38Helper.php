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

use function json_encode;

class Sep38Helper
{

    /**
     * @return array<Sep38AssetInfo> the assets having sep38 support enabled.
     */
    public static function getSupportedAssets(): array
    {
        /**
         * @var array<Sep38AssetInfo> $result
         */
        $result = [];

        $assets = AnchorAsset::whereSep38Enabled(true)->get();
        if ($assets === null || count($assets) === 0) {
            Log::warning('No assets in DB.', ['context' => 'sep38']);

            return $result;
        }

        foreach ($assets as $asset) {
            try {
                $result[] = self::sep38AssetInfoFromAnchorAsset($asset);
            } catch (InvalidAsset $iA) {
                Log::error(
                    'Invalid asset in DB.',
                    ['context' => 'sep38', 'asset_id' => $asset->id, 'error' => $iA->getMessage(), 'exception' => $iA]
                );
            }
        }

        return $result;
    }

    /**
     * @param Sep38PricesRequest $request the request data
     * @return array<Sep38BuyAsset> the result containing the prices
     * @throws AnchorFailure
     */
    public static function getPrices(Sep38PricesRequest $request) : array
    {
        Log::debug(
            'Retrieving the prices from DB.',
            ['context' => 'sep38', 'operation' => 'get_prices', 'request' => json_encode($request)],
        );

        /**
         * @var array<Sep38BuyAsset> $result
         */
        $result = [];
        $rates = Sep38Rate::whereSellAsset($request->sellAsset->getStringRepresentation())->get();
        if ($rates === null || count($rates) === 0) {
            Log::warning(
                'No rates in DB.',
                ['context' => 'sep38', 'operation' => 'get_prices',
                    'sell_asset' => $request->sellAsset->getStringRepresentation()],
            );

            return $result;
        }

        $sellAmount = floatval($request->sellAmount);
        Log::debug(
            'Rates found in DB.',
            ['context' => 'sep38', 'operation' => 'get_prices', 'rates' => json_encode($rates)],
        );

        foreach ($rates as $rate) {
            try {
                $fee = ($rate->fee_percent / 100) * $sellAmount;
                $buyAmount = ($sellAmount - $fee) / $rate->rate;
                $totalPrice = $sellAmount / $buyAmount;
                $buyAsset = IdentificationFormatAsset::fromString($rate->buy_asset);
                $decimals = self::significantDecimalsForAsset($buyAsset);
                Log::debug(
                    'Calculating the price out of rate.',
                    ['context' => 'sep38', 'operation' => 'get_prices', 'fee' => $fee, 'buy_amount' => $buyAmount,
                        'total_price' => $totalPrice, 'buy_asset' => $buyAsset->getStringRepresentation(),
                        'decimals' => $decimals, 'rate' => json_encode($rate),
                    ],
                );

                $result[] = new Sep38BuyAsset(
                    asset: $buyAsset,
                    price: strval($totalPrice),
                    decimals: $decimals,
                );
            } catch (InvalidAsset $iA) {
                Log::error(
                    'Invalid asset in DB.',
                    ['context' => 'sep38', 'operation' => 'get_prices', 'error' => $iA->getMessage(),
                        'exception' => $iA, 'buy_asset' => $rate->buy_asset],
                );
            }
        }
        Log::debug(
            'The prices from DB.',
            ['context' => 'sep38', 'operation' => 'get_prices', 'result' => json_encode($result)],
        );

        return $result;
    }

    /**
     * @param Sep38PriceRequest $request the request data
     * @return Sep38Price the result containing the price
     * @throws AnchorFailure
     */
    public static function getPrice(Sep38PriceRequest $request) : Sep38Price
    {
        Log::debug(
            'Calculating price.',
            ['context' => 'sep38', 'operation' => 'get_price', 'request' => json_encode($request)],
        );

        $rate = Sep38Rate::where('sell_asset', $request->sellAsset->getStringRepresentation())
            ->where('buy_asset', $request->buyAsset->getStringRepresentation())->first();
        if ($rate === null) {
            Log::warning(
                'Rate not found in DB.',
                ['context' => 'sep38', 'operation' => 'get_price',
                    'sell_asset' => $request->sellAsset->getStringRepresentation(),
                    'buy_asset' => $request->buyAsset->getStringRepresentation()],
            );

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

            $sellPrice = new Sep38Price(
                totalPrice: strval($totalPrice),
                price: strval($rate->rate),
                sellAmount: strval($sellAmount),
                buyAmount: strval(round($buyAmount, $buyAssetDecimals)),
                fee: $sep38Fee,
            );
            Log::debug(
                'The sell price has been calculated successfully.',
                ['context' => 'sep38', 'operation' => 'get_price', 'price' => json_encode($sellPrice)],
            );

            return $sellPrice;
        } elseif ($request->buyAmount !== null) {
            $buyAmount = floatval($request->buyAmount);
            $fee = ($rate->fee_percent / 100) * $buyAmount;
            $sellAmount = $rate->rate * ($buyAmount + $fee);
            $sep38Fee = new TransactionFeeInfo(strval(round($fee, $buyAssetDecimals)), $request->buyAsset);
            $totalPrice = $sellAmount / $buyAmount;

            $buyPrice = new Sep38Price(
                totalPrice: strval($totalPrice),
                price: strval($rate->rate),
                sellAmount: strval(round($sellAmount, $sellAssetDecimals)),
                buyAmount: strval($buyAmount),
                fee: $sep38Fee,
            );
            Log::debug(
                'The buy price has been calculated successfully.',
                ['context' => 'sep38', 'operation' => 'get_price', 'price' => json_encode($buyPrice)],
            );

            return $buyPrice;
        }
        Log::warning(
            'Failed to calculate the price, either sell_amount or buy_amount must be provided.',
            ['context' => 'sep38', 'operation' => 'get_price', 'sell_amount' => $request->sellAmount,
                'buy_amount' => $request->buyAmount],
        );

        throw new AnchorFailure('either sell_amount or buy_amount must be provided');
    }

    /**
     * @param Sep38QuoteRequest $request the request containing the data for the quote to be created and saved.
     * @return Sep38Quote the created and saved quote
     *
     * @throws AnchorFailure if any error occurs or the quote can not be provided.
     */
    public static function addQuote(Sep38QuoteRequest $request) : Sep38Quote
    {
        Log::debug(
            'Requesting firm quote.',
            ['context' => 'sep38', 'operation' => 'add_quote', 'request' => json_encode($request)],
        );

        $now = new DateTime();
        $hours = 24;
        $expiresAt = (clone $now)->add(new DateInterval("PT{$hours}H"));
        if ($request->expireAfter !== null && $request->expireAfter > $expiresAt) {
            Log::warning(
                'Expired, firm quote can not be calculated.',
                ['context' => 'sep38', 'operation' => 'add_quote',
                    'request_expires_after' => json_encode($request->expireAfter),
                    'calculated_expires_at' => json_encode($expiresAt)],
            );

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
        Log::debug(
            'The calculated and saved firm quote.',
            ['context' => 'sep38', 'operation' => 'add_quote', 'quote' => json_encode($exchangeQuote)],
        );

        try {
            return self::sep38QuoteFromExchangeQuote($exchangeQuote);
        } catch (Throwable $t) {
            Log::debug(
                'Error creating the quote.',
                ['context' => 'sep38', 'operation' => 'add_quote',
                    'error' => $t->getMessage(), 'exception' => $t],
            );

            throw new AnchorFailure('error creating quote');
        }
    }

    /**
     * @throws AnchorFailure
     * @throws QuoteNotFoundForId
     */
    public static function getQuoteById(string $id, string $accountId, ?string $accountMemo = null) : Sep38Quote
    {
        Log::debug(
            'Retrieving quote from DB.',
            ['context' => 'sep38', 'operation' => 'get_quote_by_id', 'id' => $id, 'account_id' => $accountId,
                'account_memo' => $accountMemo],
        );

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
            Log::debug(
                'Failed to convert the quote model to data.',
                ['context' => 'sep38', 'operation' => 'get_quote_by_id', 'error' =>  $t->getMessage(),
                    'exception' => $t, 'quote' => json_encode($exchangeQuote)],
            );

            throw new AnchorFailure('error reading quote');
        }
    }

    /**
     * @throws AnchorFailure
     */
    private static function significantDecimalsForAsset(IdentificationFormatAsset $asset) : int
    {
        Log::debug(
            'Retrieving significant decimals by asset.',
            ['context' => 'sep38', 'asset' => json_encode($asset)],
        );
        $dbAsset = AnchorAsset::where('code', $asset->getCode())
            ->where('issuer', $asset->getIssuer())
            ->where('schema', $asset->getSchema())->first();
        if ($dbAsset === null) {
            Log::debug(
                'Asset not found.',
                ['context' => 'sep38'],
            );
            throw new AnchorFailure('asset not found ' . $asset->getStringRepresentation());
        }
        $significantDecimals = $dbAsset->significant_decimals;
        Log::debug(
            'The significant decimals by asset.',
            ['context' => 'sep38', 'asset' => json_encode($asset), 'significant_decimals' => $significantDecimals],
        );

        return $significantDecimals;
    }
    /**
     * @throws InvalidAsset
     */
    public static function sep38AssetInfoFromAnchorAsset(AnchorAsset $anchorAsset): Sep38AssetInfo
    {
        $formattedAsset = new IdentificationFormatAsset(
            $anchorAsset->schema,
            $anchorAsset->code,
            $anchorAsset->issuer,
        );
        $result = new Sep38AssetInfo($formattedAsset);

        $sep38Info = $anchorAsset->sep38_info;
        if ($sep38Info !== null) {
            $jsonData = json_decode($sep38Info, true);
            if ($jsonData === null) {
                Log::debug(
                    'Failed to parse SEP-38 asset info string to JSON.',
                    ['context' => 'sep38', 'asset' => json_encode($anchorAsset)],
                );

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
                    if (is_string($code)) {
                        $countryCodes[] = $code;
                    }
                }
                $result->countryCodes = $countryCodes;
            }
        }else {
            Log::debug(
                'SEP-38 asset info is null.',
                ['context' => 'sep38', 'asset' => json_encode($anchorAsset)],
            );
        }
        Log::debug(
            'The built SEP-38 asset info model.',
            ['context' => 'sep38', 'model' => json_encode($result)],
        );

        return $result;
    }

    private static function sep38DeliveryMethodFromJson(array $method) : ?Sep38DeliveryMethod
    {
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
    private static function sep38QuoteFromExchangeQuote(Sep38ExchangeQuote $exchangeQuote) : Sep38Quote
    {
        Log::debug(
            'Converting DB data to model.',
            ['context' => 'sep38', 'exchange_quote' => json_encode($exchangeQuote)],
        );

        $feeJsonData = json_decode($exchangeQuote->fee, true);
        if ($feeJsonData === null) {
            Log::debug(
                'Invalid fee data in quote, fee JSON must be set.',
                ['context' => 'sep38', 'exchange_quote' => json_encode($exchangeQuote)],
            );

            throw new Exception('invalid fee data in quote ' . $exchangeQuote->id);
        }

        if (!isset($feeJsonData['total']) || !isset($feeJsonData['asset'])) {
            Log::debug(
                'Invalid fee data in quote, total and asset must be set.',
                ['context' => 'sep38', 'exchange_quote' => json_encode($exchangeQuote)],
            );

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
                    Log::debug(
                        'Invalid fee data in quote, total and asset must be set.',
                        ['context' => 'sep38', 'exchange_quote' => json_encode($exchangeQuote)],
                    );

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
        $data = new Sep38Quote(
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
        Log::debug(
            'The built data.',
            ['context' => 'sep38', 'data' => json_encode($data)],
        );

        return $data;
    }
}
