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
        Schema::create('variant_template_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id'); // Tạo cột variant_id
            $table->unsignedBigInteger('template_attribute_value_id'); // Tạo cột template_attribute_value_id
            $table->primary(['variant_id', 'template_attribute_value_id']); // Tạo khóa chính cho cả hai cột

            // Tạo khóa ngoại cho variant_id, liên kết với bảng template_variants
            $table->foreign('variant_id')->references('id')->on('template_variants')->onDelete('cascade');

            // Tạo khóa ngoại cho template_attribute_value_id, liên kết với bảng template_attribute_values
            $table->foreign('template_attribute_value_id')->references('id')->on('template_attribute_values')->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variant_template_attribute_values');
    }
};
