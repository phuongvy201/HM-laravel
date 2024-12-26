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
        Schema::create('template_values', function (Blueprint $table) {
            $table->id();  // Tạo trường id (Primary Key)
            $table->unsignedBigInteger('template_id');  // Tạo trường field_id (Foreign Key)
            $table->string('name');  // Tạo trường name (VARCHAR)
            $table->string('value');  // Tạo trường value (VARCHAR)
            $table->decimal('additional_price', 10, 2)->default(0)->nullable();  // Tạo trường additional_price (DECIMAL)
            $table->string('image_url')->nullable();  // Tạo trường image_url (VARCHAR), cho phép null
            $table->timestamps();  // Tạo trường created_at và updated_at

            // Khóa ngoại cho field_id
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('template_values');
    }
};
