<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep38Rates
 *
 * @property int $id
 * @property string $sell_asset
 * @property string $buy_asset
 * @property float $rate
 * @property float $fee_percent
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereBuyAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereFeePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereSellAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereUpdatedAt($value)
 * @property int $buy_asset_decimals
 * @method static \Illuminate\Database\Eloquent\Builder|Sep38Rate whereBuyAssetDecimals($value)
 * @mixin \Eloquent
 */
class Sep38Rate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep38_rates';
}
