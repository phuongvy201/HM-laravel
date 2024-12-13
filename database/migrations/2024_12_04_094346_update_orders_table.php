<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Xóa cột seller_id
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('seller_id');
        });

        // Thay đổi cột customer_id thành nullable
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Khôi phục lại cột seller_id
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_id');
        });

        // Khôi phục lại cột customer_id thành non-nullable
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();
        });
    }
};
