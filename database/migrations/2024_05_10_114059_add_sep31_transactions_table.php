<?php

use ArgoNavis\PhpAnchorSdk\shared\Sep31TransactionStatus;
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
        Schema::create('sep31_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(Uuid::uuid4());
            $table->string('stellar_transaction_id')->nullable();
            $table->string('external_transaction_id')->nullable();
            $table->string('status')->default(Sep31TransactionStatus::PENDING_RECEIVER);
            $table->integer('status_eta')->nullable();
            $table->float('amount_expected')->nullable();
            $table->string('amount_in')->nullable();
            $table->string('amount_in_asset')->nullable();
            $table->float('amount_out')->nullable();
            $table->string('amount_out_asset')->nullable();
            $table->float('amount_fee')->nullable();
            $table->string('amount_fee_asset')->nullable();
            $table->string('stellar_account_id')->nullable();
            $table->string('stellar_memo')->nullable();
            $table->string('stellar_memo_type')->nullable();
            $table->string('client_domain')->nullable();
            $table->dateTime('tx_started_at');
            $table->dateTime('tx_completed_at')->nullable();
            $table->dateTime('tx_updated_at')->nullable();
            $table->dateTime('transfer_received_at')->nullable();
            $table->json('stellar_transactions')->nullable();
            $table->string('sep10_account')->nullable();
            $table->string('sep10_account_memo')->nullable();
            $table->string('quote_id')->nullable();
            $table->string('sender_id')->nullable();
            $table->string('receiver_id')->nullable();
            $table->string('callback_url')->nullable();
            $table->text('message')->nullable();
            $table->json('refunds')->nullable();
            $table->string('refund_memo')->nullable();
            $table->string('refund_memo_type')->nullable();
            $table->json('fee_details')->nullable();
            $table->string('required_info_message')->nullable();
            $table->json('required_customer_info_updates')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep31_transactions');
    }
};
