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
        Schema::table('anchor_assets', function (Blueprint $table) {
            $table->float('send_fee_fixed')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anchor_assets', function (Blueprint $table) {
            $table->dropColumn('send_fee_fixed');
        });
    }
};
