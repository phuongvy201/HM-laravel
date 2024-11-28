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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('transaction_id')->unique(); // Mã giao dịch từ cổng thanh toán
            $table->decimal('amount', 12, 2); // Số tiền giao dịch
            $table->string('payment_method'); // Phương thức thanh toán (ví dụ: vnpay, momo, banking)
            $table->string('status'); // Trạng thái giao dịch (success, failed, pending)
            $table->text('response_data')->nullable(); // Dữ liệu phản hồi từ cổng thanh toán
            $table->string('bank_code')->nullable(); // Mã ngân hàng nếu thanh toán qua banking
            $table->string('card_type')->nullable(); // Loại thẻ sử dụng (nếu có)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
