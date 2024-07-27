<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\AnchorAsset
 *
 * @property int $id
 * @property string $code
 * @property string|null $issuer
 * @property int $deposit_enabled
 * @property float|null $deposit_fee_fixed
 * @property float|null $deposit_fee_percent
 * @property float|null $deposit_fee_minimum
 * @property float|null $deposit_min_amount
 * @property float|null $deposit_max_amount
 * @property int $withdrawal_enabled
 * @property float|null $withdrawal_fee_fixed
 * @property float|null $withdrawal_fee_percent
 * @property float|null $withdrawal_fee_minimum
 * @property float|null $withdrawal_min_amount
 * @property float|null $withdrawal_max_amount
 * @property int $significant_decimals
 * @property string $schema
 * @property int $sep24_enabled
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset query()
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereDepositEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereDepositFeeFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereDepositFeeMinimum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereDepositFeePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereDepositMaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereDepositMinAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSchema($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep24Enabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSignificantDecimals($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereWithdrawalEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereWithdrawalFeeFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereWithdrawalFeeMinimum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereWithdrawalFeePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereWithdrawalMaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereWithdrawalMinAmount($value)
 * @property int $sep38_enabled
 * @property string|null $sep38_info
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep38Enabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep38Info($value)
 * @property int $sep06_enabled
 * @property string|null $sep06_deposit_methods
 * @property string|null $sep06_withdraw_methods
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep06DepositMethods($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep06Enabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep06WithdrawMethods($value)
 * @property int $sep06_deposit_exchange_enabled
 * @property int $sep06_withdraw_exchange_enabled
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep06DepositExchangeEnabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep06WithdrawExchangeEnabled($value)
 * @property int $sep31_enabled
 * @property string|null $sep31_info
 * @property float|null $send_fee_fixed
 * @property float|null $send_fee_percent
 * @property float|null $send_min_amount
 * @property float|null $send_max_amount
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSendFeeFixed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSendFeePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSendMaxAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSendMinAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep31Enabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AnchorAsset whereSep31Info($value)
 * @mixin \Eloquent
 */
class AnchorAsset extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anchor_assets';

    protected $fillable = [
        'code',
        'issuer',
        'deposit_enabled',
        'deposit_fee_fixed',
        'deposit_fee_percent',
        'deposit_fee_minimum',
        'deposit_min_amount',
        'deposit_max_amount',
        'withdrawal_enabled',
        'withdrawal_fee_fixed',
        'withdrawal_fee_percent',
        'withdrawal_fee_minimum',
        'withdrawal_min_amount',
        'withdrawal_max_amount',
        'significant_decimals',
        'schema',
        'sep24_enabled',
        'sep38_enabled',
        'sep38_info',
        'sep06_enabled',
        'sep06_deposit_methods',
        'sep06_withdraw_methods',
        'sep06_deposit_exchange_enabled',
        'sep06_withdraw_exchange_enabled',
        'sep31_enabled',
        'sep31_info',
        'send_fee_fixed',
        'send_fee_percent',
        'send_min_amount',
        'send_max_amount'
    ];
}
