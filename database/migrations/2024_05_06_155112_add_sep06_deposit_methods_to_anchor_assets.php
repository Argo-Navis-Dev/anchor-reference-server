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
            $table->string('sep06_deposit_methods')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anchor_assets', function (Blueprint $table) {
            $table->dropColumn('sep06_deposit_methods');
        });
    }
};
