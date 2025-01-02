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
        Schema::create('template_attribute_values', function (Blueprint $table) {
            $table->id(); // Tạo cột id tự động
            $table->unsignedBigInteger('template_attribute_id'); // Tạo cột template_attribute_id
            $table->string('value', 255); // Tạo cột value (S/M/L hoặc Cotton/Polyester)
            $table->timestamps(); // Tạo cột created_at và updated_at

            // Tạo khóa ngoại cho template_attribute_id
            $table->foreign('template_attribute_id')->references('id')->on('template_attributes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_attribute_values');
    }
};
