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
        Schema::create('payment_gateways_test', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sandbox_client_id');
            $table->string('sandbox_client_secret');
            $table->decimal('daily_limit', 10, 2);
            $table->decimal('current_daily_amount', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_reset_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways_test');
    }
};
