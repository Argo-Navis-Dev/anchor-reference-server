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
        Schema::table('sep06_transactions', function (Blueprint $table) {
            $table->string('stellar_paging_token')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sep06_transactions', function (Blueprint $table) {
            $table->dropColumn('stellar_paging_token');
        });
    }
};
