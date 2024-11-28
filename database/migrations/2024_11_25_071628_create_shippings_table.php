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
        Schema::create('shippings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('tracking_number')->nullable(); // Mã vận đơn
            $table->string('shipping_method'); // Phương thức vận chuyển (express, standard...)
            
            // Thông tin người nhận
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('address');
            $table->string('country'); // Phường/Xã
            $table->string('city'); // Quận/Huyện
            $table->string('zip_code'); // Quận/Huyện
            
            // Chi phí và trạng thái
            $table->decimal('shipping_cost', 10, 2); // Phí vận chuyển
            $table->string('status')->default('pending'); // Trạng thái đơn ship
            $table->timestamp('estimated_delivery_date')->nullable(); // Ngày dự kiến giao hàng
            $table->timestamp('actual_delivery_date')->nullable(); // Ngày thực tế giao hàng
            
            // Ghi chú
            $table->text('shipping_notes')->nullable(); // Ghi chú giao hàng
            $table->text('internal_notes')->nullable(); // Ghi chú nội bộ
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shippings');
    }
};
