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
        Schema::create('template_variants', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự động
            $table->unsignedBigInteger('template_id'); // Tạo cột template_id
            $table->string('sku', 100); // Tạo cột sku (Stock Keeping Unit)
            $table->text('image')->nullable(); // Tạo cột image
            $table->decimal('price', 10, 2); // Tạo cột price (giá sản phẩm)
            $table->integer('quantity'); // Tạo cột quantity (số lượng sản phẩm)
            $table->timestamps(); // Tạo cột created_at và updated_at

            // Tạo khóa ngoại cho template_id, liên kết với bảng product_templates
            $table->foreign('template_id')->references('id')->on('product_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_variants');
    }
};
