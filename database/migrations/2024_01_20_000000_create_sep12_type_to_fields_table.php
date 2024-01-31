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
        Schema::create('sep12_type_to_fields', function (Blueprint $table) {
            $table->id('id')->unique()->autoIncrement();
            $table->string('type')->unique();
            $table->string('required_fields')->nullable();
            $table->string('optional_fields')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep12_type_to_fields');
    }
};
