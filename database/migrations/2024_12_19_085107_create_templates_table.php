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
        Schema::create('templates', function (Blueprint $table) {
            $table->id();  // Tạo trường id (Primary Key)
            $table->unsignedBigInteger('user_id');  // Tạo trường user_id (Foreign Key)
            $table->string('template_name');  // Tạo trường name (VARCHAR)
            $table->string('description');  // Tạo trường name (VARCHAR)
            $table->unsignedBigInteger('category_id');  // Tạo trường category_id (Foreign Key)
            $table->string('image');  // Tạo trường name (VARCHAR)
            $table->timestamps();  // Tạo trường created_at và updated_at

            // Khóa ngoại cho user_id
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
