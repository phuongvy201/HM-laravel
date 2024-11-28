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
        Schema::create('profile_shop', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name'); // Tên shop
            $table->unsignedBigInteger('owner_id'); // ID người sở hữu
            $table->text('description')->nullable(); // Mô tả shop
            $table->string('logo_url')->nullable(); // URL logo shop
            $table->string('banner_url')->nullable(); // URL banner shop
            $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps(); // created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profile_shop');
    }
};
