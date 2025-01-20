<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTemplateImagesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('template_images', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự động
            $table->unsignedBigInteger('template_id'); // Tạo cột template_id
            $table->text('url'); // Tạo cột url để lưu đường dẫn hình ảnh
       
            $table->timestamps(); // Tạo cột created_at và updated_at

            // Tạo khóa ngoại cho template_id
            $table->foreign('template_id')->references('id')->on('product_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_images');
    }
}
