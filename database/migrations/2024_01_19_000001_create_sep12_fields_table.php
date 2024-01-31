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
        Schema::create('sep12_fields', function (Blueprint $table) {
            $table->id('id')->unique()->autoIncrement();
            $table->string('key');
            $table->string('type');
            $table->string('desc');
            $table->string('choices')->nullable();
            $table->boolean('requires_verification')->default(false);
            $table->string('lang')->default('en');
            $table->timestamps();
            $table->unique(['key', 'lang']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep12_fields');
    }
};
