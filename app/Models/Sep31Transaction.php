<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Sep31Transaction
 *
 * @property string $id
 * @property string|null $stellar_transaction_id
 * @property string|null $external_transaction_id
 * @property string $status
 * @property int|null $status_eta
 * @property string|null $amount_in
 * @property string|null $amount_in_asset
 * @property float|null $amount_out
 * @property string|null $amount_out_asset
 * @property float|null $amount_fee
 * @property string|null $amount_fee_asset
 * @property string|null $stellar_account_id
 * @property string|null $stellar_memo
 * @property string|null $stellar_memo_typ
 * @property string|null $client_domain
 * @property string $tx_started_at
 * @property string|null $tx_completed_at
 * @property string|null $tx_updated_at
 * @property string|null $transfer_received_at
 * @property string|null $stellar_transactions
 * @property string|null $sep10_account
 * @property string|null $sep10_account_memo
 * @property string|null $quote_id
 * @property string|null $sender_id
 * @property string|null $receiver_id
 * @property string|null $callback_url
 * @property string|null $message
 * @property string|null $refunds
 * @property string|null $refund_memo
 * @property string|null $refund_memo_type
 * @property string|null $fee_details
 * @property string|null $required_info_message
 * @property string|null $required_customer_info_updates
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountFeeAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountInAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountOutAsset($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereCallbackUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereClientDomain($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereExternalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereFeeDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereQuoteId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereReceiverId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereRefundMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereRefundMemoType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereRefunds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereRequiredCustomerInfoUpdates($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereRequiredInfoMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereSep10Account($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereSep10AccountMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStatusEta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStellarAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStellarMemo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStellarMemoTyp($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStellarTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereStellarTransactions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereTransferReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereTxCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereTxStartedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereTxUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereUpdatedAt($value)
 * @property float|null $amount_expected
 * @method static \Illuminate\Database\Eloquent\Builder|Sep31Transaction whereAmountExpected($value)
 * @mixin \Eloquent
 */
class Sep31Transaction extends Model
{
    use HasUuids;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sep31_transactions';
}
