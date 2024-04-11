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
        Schema::create('sep12_provided_fields', function (Blueprint $table) {
            $table->id('id')->unique()->autoIncrement();
            $table->uuid('sep12_customer_id');
            $table->unsignedBigInteger('sep12_field_id');
            $table->string('status')->nullable();
            $table->string('error')->nullable();
            $table->string('string_value')->nullable();
            $table->integer('number_value')->nullable();
            $table->date('date_value')->nullable();
            $table->string('verification_code')->nullable();
            $table->boolean('verified')->default(false);
            $table->timestamps();
            $table->foreign('sep12_field_id')->references('id')->on('sep12_fields');
            $table->foreign('sep12_customer_id')->references('id')->on('sep12_customers');
            $table->unique(['sep12_customer_id', 'sep12_field_id']);
        });
        //Add binary_value column separetedly in order to specify the type.
        DB::statement('ALTER TABLE sep12_provided_fields ADD binary_value mediumblob AFTER number_value');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep12_provided_fields');
    }
};
