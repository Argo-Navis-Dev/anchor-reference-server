<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sep06_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(Uuid::uuid4());
            $table->string('stellar_transaction_id')->nullable();
            $table->string('external_transaction_id')->nullable();
            $table->string('status');
            $table->integer('status_eta')->nullable();
            $table->string('kind'); // deposit, withdraw, deposit_exchange, withdraw-exchange
            $table->dateTime('tx_started_at');
            $table->dateTime('tx_completed_at')->nullable();
            $table->dateTime('tx_updated_at')->nullable();
            $table->dateTime('transfer_received_at')->nullable();
            $table->string('type');
            $table->string('request_asset_code')->nullable();
            $table->string('request_asset_issuer')->nullable();
            $table->float('amount_in')->nullable();
            $table->float('amount_out')->nullable();
            $table->float('amount_fee')->nullable();
            $table->string('amount_in_asset')->nullable();
            $table->string('amount_out_asset')->nullable();
            $table->string('amount_fee_asset')->nullable();
            $table->float('amount_expected')->nullable();
            $table->string('sep10_account')->nullable();
            $table->string('sep10_account_memo')->nullable();
            $table->string('withdraw_anchor_account')->nullable();
            $table->string('from_account')->nullable();
            $table->string('to_account')->nullable();
            $table->string('memo')->nullable();
            $table->string('memo_type')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('more_info_url')->nullable();
            $table->text('message')->nullable();
            $table->json('refunds')->nullable();
            $table->string('refund_memo')->nullable();
            $table->string('refund_memo_type')->nullable();
            $table->text('required_info_message')->nullable();
            $table->json('required_info_updates')->nullable();
            $table->text('required_customer_info_message')->nullable();
            $table->json('required_customer_info_updates')->nullable();
            $table->string('client_domain')->nullable();
            $table->string('client_name')->nullable();
            $table->json('fee_details')->nullable();
            $table->json('instructions')->nullable();
            $table->string('claimable_balance_id')->nullable();
            $table->boolean('claimable_balance_supported')->default(false);
            $table->json('stellar_transactions')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep06_transactions');
    }
};
