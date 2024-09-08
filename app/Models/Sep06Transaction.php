<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep06Transaction
 *
 * @property string $id
 * @property string|null $stellar_transaction_id
 * @property string|null $external_transaction_id
 * @property string $status
 * @property int|null $status_eta
 * @property string $kind
 * @property string $tx_started_at
 * @property string|null $tx_completed_at
 * @property string|null $tx_updated_at
 * @property string|null $transfer_received_at
 * @property string $type
 * @property string|null $request_asset_code
 * @property string|null $request_asset_issuer
 * @property float|null $amount_in
 * @property float|null $amount_out
 * @property float|null $amount_fee
 * @property string|null $amount_in_asset
 * @property string|null $amount_out_asset
 * @property string|null $amount_fee_asset
 * @property float|null $amount_expected
 * @property string|null $sep10_account
 * @property string|null $sep10_account_memo
 * @property string|null $withdraw_anchor_account
 * @property string|null $from_account
 * @property string|null $to_account
 * @property string|null $memo
 * @property string|null $memo_type
 * @property string|null $quote_id
 * @property string|null $message
 * @property string|null $refunds
 * @property string|null $refund_memo
 * @property string|null $refund_memo_type
 * @property string|null $required_info_message
 * @property string|null $required_info_updates
 * @property string|null $required_customer_info_message
 * @property string|null $required_customer_info_updates
 * @property string|null $client_domain
 * @property string|null $client_name
 * @property string|null $fee_details
 * @property string|null $claimable_balance_id
 * @property int $claimable_balance_supported
 * @property string|null $stellar_transactions
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountExpected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountFeeAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountInAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereAmountOutAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereClaimableBalanceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereClaimableBalanceSupported($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereClientDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereClientName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereExternalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereFeeDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereFromAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereKind($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereMemoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereQuoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRefundMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRefundMemoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRefunds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRequestAssetCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRequestAssetIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRequiredCustomerInfoMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRequiredCustomerInfoUpdates($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRequiredInfoMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereRequiredInfoUpdates($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereSep10Account($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereSep10AccountMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereStatusEta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereStellarTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereStellarTransactions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereToAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereTransferReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereTxCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereTxStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereTxUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereWithdrawAnchorAccount($value)
 * @property string|null $more_info_url
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereMoreInfoUrl($value)
 * @property string|null $instructions
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereInstructions($value)
 * @property string|null $stellar_paging_token
 * @method static \Illuminate\Database\Eloquent\Builder|Sep06Transaction whereStellarPagingToken($value)
 * @mixin \Eloquent
 */
class Sep06Transaction extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep06_transactions';

    protected $fillable = [
        'id',
        'stellar_transaction_id',
        'external_transaction_id',
        'status',
        'status_eta',
        'kind',
        'tx_started_at',
        'tx_completed_at',
        'tx_updated_at',
        'transfer_received_at',
        'type',
        'request_asset_code',
        'request_asset_issuer',
        'amount_in',
        'amount_out',
        'amount_fee',
        'amount_in_asset',
        'amount_out_asset',
        'amount_fee_asset',
        'amount_expected',
        'sep10_account',
        'sep10_account_memo',
        'withdraw_anchor_account',
        'from_account',
        'to_account',
        'memo',
        'memo_type',
        'quote_id',
        'more_info_url',
        'message',
        'refunds',
        'refund_memo',
        'refund_memo_type',
        'required_info_message',
        'required_info_updates',
        'required_customer_info_message',
        'required_customer_info_updates',
        'client_domain',
        'client_name',
        'fee_details',
        'instructions',
        'claimable_balance_id',
        'claimable_balance_supported',
        'stellar_transactions',
        'created_at',
        'updated_at',
    ];
}
