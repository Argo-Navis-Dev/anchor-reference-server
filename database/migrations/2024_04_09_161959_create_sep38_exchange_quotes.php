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
        Schema::create('sep38_exchange_quotes', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(Uuid::uuid4());
            $table->string('context');
            $table->timestamp('expires_at');
            $table->string('price');
            $table->string('total_price');
            $table->string('sell_asset');
            $table->string('sell_amount');
            $table->string('sell_delivery_method')->nullable();
            $table->string('buy_asset');
            $table->string('buy_amount');
            $table->string('buy_delivery_method')->nullable();
            $table->json('fee');
            $table->string('account_id');
            $table->string('account_memo')->nullable();
            $table->string('transaction_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sep38_exchange_quotes', function (Blueprint $table) {
            Schema::dropIfExists('sep38_exchange_quotes');
        });
    }
};
