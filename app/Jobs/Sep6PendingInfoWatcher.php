<?php

declare(strict_types=1);

// Copyright 2024 Argo Navis Dev. All rights reserved.
// Use of this source code is governed by a license that can be
// found in the LICENSE file.

namespace App\Jobs;

use App\Models\Sep06Transaction;
use App\Stellar\Sep12Customer\Sep12Helper;
use ArgoNavis\PhpAnchorSdk\shared\CustomerStatus;
use ArgoNavis\PhpAnchorSdk\shared\Sep06TransactionStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * This job checks SEP-6 transactions that are waiting for the user to have their KYC data accepted
 * (transaction status PENDING_CUSTOMER_INFO_UPDATE). If the user already has their KYC data accepted,
 * the status of the transaction is set to PENDING_USER_TRANSFER_START.
 */
class Sep6PendingInfoWatcher implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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
    public $timeout = 30;

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
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        $sep6Txs = Sep06Transaction::where(
            'status',
            '=',
            Sep06TransactionStatus::PENDING_CUSTOMER_INFO_UPDATE,
        )->get();

        if ($sep6Txs === null || count($sep6Txs) === 0) {
            // no transactions are waiting for the KYC data of the user to be accepted.
            return;
        }

        foreach($sep6Txs as $sep6Tx) {

            $sep10AccountId = $sep6Tx->sep10_account;
            $sep10AccountMemo = $sep6Tx->sep10_account_memo;

            // get the status of the users KYC data
            $sep12Customer = Sep12Helper::getSep12CustomerByAccountId(
                accountId: $sep10AccountId,
                memo: $sep10AccountMemo);
            if ($sep12Customer === null) {
                continue;
            }
            $kycStatus = $sep12Customer->status;

            if ($kycStatus !== CustomerStatus::ACCEPTED) {
                // if not yet accepted, we continue with the next transaction
                continue;
            }

            // now that the user provided the kyc data we can set the status to PENDING_USER_TRANSFER_START
            // hint: why not check trust here for deposit? a: the user should first send the funds.
            $sep6Tx->status = Sep06TransactionStatus::PENDING_USER_TRANSFER_START;
            $sep6Tx->save();
            $this->maybeMakeCallback($sep6Tx->id);
        }
    }

    private function maybeMakeCallback(string $sep6TransactionId) : void {

    }
}
