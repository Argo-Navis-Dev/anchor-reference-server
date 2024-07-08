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
        Schema::create('sep08_kyc_status', function (Blueprint $table) {
            $table->id('id')->unique()->autoIncrement();
            $table->string('stellar_address')->unique();
            $table->boolean('approved')->default(false);
            $table->boolean('rejected')->default(false);
            $table->boolean('pending')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sep08_kyc_status', function (Blueprint $table) {
            Schema::dropIfExists('sep08_kyc_status');
        });
    }

};
