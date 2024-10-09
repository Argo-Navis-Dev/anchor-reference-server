<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Jobs;

use App\Models\Sep06Transaction;
use App\Stellar\Sep06Transfer\Sep06Helper;
use App\Stellar\Shared\SepHelper;
use ArgoNavis\PhpAnchorSdk\exception\AccountNotFound;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use ArgoNavis\PhpAnchorSdk\Stellar\TrustlinesHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Soneso\StellarSDK\Exceptions\HorizonRequestException;

/**
 * This Job handles SEP-6 DEPOSIT and DEPOSIT_EXCHANGE transactions, that have the status
 * PENDING_TRUST. These transactions are waiting for the user to establish a trustline
 * to the anchor asset they need to receive, so that the anchor can make the payment.
 * The job checks for each found transaction if the trustline has already been established
 * by th user, and if yes, it changes the transaction status to PENDING_USER_TRANSFER_START.
 */
class Sep6DepositPendingTrustWatcher implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private string $horizonUrl;

    /**
     * The number of times the job may be attempted.
     * 0 = indefinitely
     * @var int
     */
    public $tries = 0;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     *
     * @var int
     */
    public $maxExceptions = 0;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->horizonUrl = config('stellar.app.horizon_url');
        TrustlinesHelper::setLogger(Log::getLogger());
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // find all deposit transactions that are waiting for a trustline to be established.
        $sep6DepositTxs = Sep06Transaction::where(
            'status',
            '=',
            Sep06TransactionStatus::PENDING_TRUST,
        )->whereIn('kind', [Sep06Helper::KIND_DEPOSIT, Sep06Helper::KIND_DEPOSIT_EXCHANGE])
            ->get();

        if ($sep6DepositTxs === null || count($sep6DepositTxs) === 0) {
            // no transactions found
            return;
        }

        foreach($sep6DepositTxs as $sep6DepositTx) {
            $receiverAccountId = $sep6DepositTx->to_account;
            $assetCode = $sep6DepositTx->request_asset_code;
            $assetIssuer = $sep6DepositTx->request_asset_issuer;

            if ($receiverAccountId === null || $assetCode === null || $assetIssuer === null) {
                // incomplete data.
                $sep6DepositTx->status = Sep06TransactionStatus::INCOMPLETE;
                $sep6DepositTx->save();
                $this->maybeMakeCallback($sep6DepositTx->id);
                continue;
            }
            try {
                $hasTrustline = TrustlinesHelper::checkIfAccountTrustsAsset(
                    horizonUrl: $this->horizonUrl,
                    accountId: $receiverAccountId,
                    assetCode: $assetCode,
                    assetIssuer: $assetIssuer,
                );
                if ($hasTrustline) {
                    $sep6DepositTx->status = Sep06TransactionStatus::PENDING_USER_TRANSFER_START;
                    $sep6DepositTx->save();
                    $this->maybeMakeCallback($sep6DepositTx->id);
                }
            } catch (AccountNotFound) {
                Log::error(
                    message:'Account ' . $receiverAccountId . ' does not exist in horizon ' .
                    $this->horizonUrl . PHP_EOL,
                    context: ['Job:Sep6PendingTrustWatcher']);

                // maybe the user deleted the account in the meantime?
                $sep6DepositTx->status = Sep06TransactionStatus::ERROR;
                $sep6DepositTx->message = 'Account ' . $receiverAccountId . ' does not exist.';
                $sep6DepositTx->save();
                $this->maybeMakeCallback($sep6DepositTx->id);

            } catch (HorizonRequestException $e) {
                // could not communicate with horizon => try next time
                SepHelper::logHorizonRequestException($e, ['Job:Sep6DepositPendingTrustWatcher']);
            }
        }
    }

    private function maybeMakeCallback(string $sep6TransactionId) : void {

    }
}
