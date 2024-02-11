<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;


/**
 * App\Models\Sep24Transaction
 *
 * @property string $id
 * @property string|null $stellar_transaction_id
 * @property string|null $external_transaction_id
 * @property string $status
 * @property string $kind
 * @property string $tx_started_at
 * @property string|null $tx_completed_at
 * @property string|null $tx_updated_at
 * @property float|null $amount_expected
 * @property string|null $request_asset_code
 * @property string|null $request_asset_issuer
 * @property string|null $source_asset
 * @property string|null $destination_asset
 * @property string|null $transfer_received_at
 * @property string|null $sep10_account
 * @property string|null $sep10_account_memo
 * @property string|null $more_info_url
 * @property int|null $status_eta
 * @property string|null $status_message
 * @property string|null $withdraw_anchor_account
 * @property string|null $from_account
 * @property string|null $to_account
 * @property string|null $memo
 * @property string|null $memo_type
 * @property string|null $client_domain
 * @property string|null $claimable_balance_id
 * @property int $claimable_balance_supported
 * @property float|null $amount_in
 * @property float|null $amount_out
 * @property float|null $amount_fee
 * @property string|null $amount_in_asset
 * @property string|null $amount_out_asset
 * @property string|null $amount_fee_asset
 * @property string|null $quote_id
 * @property string|null $muxed_account
 * @property string|null $refund_memo
 * @property string|null $refund_memo_type
 * @property int $refunded
 * @property string|null $refunds
 * @property string|null $stellar_transactions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountExpected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountFeeAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountInAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereAmountOutAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereClaimableBalanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereClaimableBalanceSupported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereClientDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereDestinationAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereExternalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereFromAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereMemoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereMoreInfoUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereMuxedAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereQuoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereRefundMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereRefundMemoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereRefunded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereRefunds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereRequestAssetCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereRequestAssetIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereSep10Account($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereSep10AccountMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereSourceAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereStatusEta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereStatusMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereStellarTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereStellarTransactions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereToAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereTransferReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereTxCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereTxStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereTxUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep24Transaction whereWithdrawAnchorAccount($value)
 * @mixin \Eloquent
 */
class Sep24Transaction extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep24_transactions';
}
