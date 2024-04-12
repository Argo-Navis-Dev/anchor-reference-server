<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep38ExchangeQuote
 *
 * @property int $id
 * @property string $context
 * @property string $expires_at
 * @property string $price
 * @property string $total_price
 * @property string $sell_asset
 * @property string $sell_amount
 * @property string|null $sell_delivery_method
 * @property string $buy_asset
 * @property string $buy_amount
 * @property string|null $buy_delivery_method
 * @property string $fee
 * @property string $account_id
 * @property string|null $account_memo
 * @property string|null $transaction_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereAccountMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereBuyAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereBuyAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereBuyDeliveryMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereContext($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereSellAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereSellAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereSellDeliveryMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereTotalPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38ExchangeQuote whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Sep38ExchangeQuote extends Model
{
    use HasUuids;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep38_exchange_quotes';
}
