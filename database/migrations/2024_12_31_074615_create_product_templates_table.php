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
        Schema::create('product_templates', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự động
            $table->string('name', 255); // Tạo cột name
            $table->unsignedBigInteger('category_id'); // Tạo cột category_id
            $table->text('image'); // Tạo cột image
            $table->text('description'); // Tạo cột description
            $table->decimal('base_price', 10, 2); // Tạo cột base_price
            $table->timestamps(); // Tạo cột created_at và updated_at
            $table->unsignedBigInteger('user_id'); // Tạo cột user_id

            // Tạo khóa ngoại cho category_id nếu có bảng categories
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_templates');
    }
};
