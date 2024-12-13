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
        Schema::table('orders', function (Blueprint $table) {
            // Thêm cột seller_id kiểu bigint
            $table->bigInteger('seller_id')->unsigned();

            // Thêm khóa ngoại cho seller_id, tham chiếu đến bảng users
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Xóa khóa ngoại và cột seller_id
            $table->dropForeign(['seller_id']);
            $table->dropColumn('seller_id');
        });
    }
};
