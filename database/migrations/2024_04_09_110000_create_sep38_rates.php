<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sep38_rates', function (Blueprint $table) {
            $table->id('id')->unique()->autoIncrement();
            $table->string('sell_asset');
            $table->string('buy_asset');
            $table->float('rate');
            $table->float('fee_percent')->default(1.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sep38_rates', function (Blueprint $table) {
            Schema::dropIfExists('sep38_rates');
        });
    }
};
