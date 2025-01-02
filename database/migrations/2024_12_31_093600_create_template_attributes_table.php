<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_template_id');
            $table->string('name'); // Tên thuộc tính (ví dụ: Size, Color)
            $table->timestamps();

            // Tạo khóa ngoại đến bảng templates
            $table->foreign('product_template_id')
                ->references('id')
                ->on('product_templates')
                ->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_attributes');
    }
};
