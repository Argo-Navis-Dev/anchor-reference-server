<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sep12_customers', function (Blueprint $table) {
            $table->uuid('id')->unique()->default(Uuid::uuid4());
            $table->string('account_id');
            $table->integer('memo')->nullable();
            $table->string('status')->default('NEEDS_INFO');
            $table->string('type')->default('default');
            $table->string('message')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('lang')->default('en');
            $table->timestamps();
            $table->unique(['account_id', 'memo', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sep12_customers');
    }
};
