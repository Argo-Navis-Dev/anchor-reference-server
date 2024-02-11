<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sep24_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(Uuid::uuid4());
            $table->string('stellar_transaction_id')->nullable();
            $table->string('external_transaction_id')->nullable();
            $table->string('status'); // See ArgoNavis\PhpAnchorSdk\shared\Sep24TransactionStatus
            $table->string('kind'); // deposit or withdraw
            $table->dateTime('tx_started_at');
            $table->dateTime('tx_completed_at')->nullable();
            $table->dateTime('tx_updated_at')->nullable();
            $table->float('amount_expected')->nullable();
            $table->string('request_asset_code')->nullable();
            $table->string('request_asset_issuer')->nullable();
            $table->string('source_asset')->nullable();
            $table->string('destination_asset')->nullable();
            $table->dateTime('transfer_received_at')->nullable();
            $table->string('sep10_account')->nullable();
            $table->string('sep10_account_memo')->nullable();
            $table->string('more_info_url')->nullable();
            $table->integer('status_eta')->nullable();
            $table->string('status_message')->nullable();
            $table->string('withdraw_anchor_account')->nullable();
            $table->string('from_account')->nullable();
            $table->string('to_account')->nullable();
            $table->string('memo')->nullable();
            $table->string('memo_type')->nullable();
            $table->string('client_domain')->nullable();
            $table->string('claimable_balance_id')->nullable();
            $table->boolean('claimable_balance_supported')->default(false);
            $table->float('amount_in')->nullable();
            $table->float('amount_out')->nullable();
            $table->float('amount_fee')->nullable();
            $table->string('amount_in_asset')->nullable();
            $table->string('amount_out_asset')->nullable();
            $table->string('amount_fee_asset')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('muxed_account')->nullable();
            $table->string('refund_memo')->nullable();
            $table->string('refund_memo_type')->nullable();
            $table->boolean('refunded')->default(false);
            $table->json('refunds')->nullable();
            $table->json('stellar_transactions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep24_transactions');
    }
};
