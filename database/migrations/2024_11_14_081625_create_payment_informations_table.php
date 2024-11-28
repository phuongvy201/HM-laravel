<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('payment_informations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('order_id');
        $table->enum('payment_method', ['payment']);
        $table->timestamps();

        // Thêm foreign key constraint
        $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
    });
}

public function down()
{
    Schema::dropIfExists('payment_informations');
}
};
