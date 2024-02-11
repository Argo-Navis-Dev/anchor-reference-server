<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('anchor_assets', function (Blueprint $table) {
            $table->id('id')->unique()->autoIncrement();
            $table->string('code');
            $table->string('issuer')->nullable();
            $table->boolean('deposit_enabled')->default(true);
            $table->float('deposit_fee_fixed')->nullable();
            $table->float('deposit_fee_percent')->nullable();
            $table->float('deposit_fee_minimum')->nullable();
            $table->float('deposit_min_amount')->nullable();
            $table->float('deposit_max_amount')->nullable();
            $table->boolean('withdrawal_enabled')->default(true);
            $table->float('withdrawal_fee_fixed')->nullable();
            $table->float('withdrawal_fee_percent')->nullable();
            $table->float('withdrawal_fee_minimum')->nullable();
            $table->float('withdrawal_min_amount')->nullable();
            $table->float('withdrawal_max_amount')->nullable();
            $table->integer('significant_decimals')->default(2);
            $table->string('schema'); // stellar or iso4217
            $table->boolean('sep24_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anchor_assets');
    }
};
