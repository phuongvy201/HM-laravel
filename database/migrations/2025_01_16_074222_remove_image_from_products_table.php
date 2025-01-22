<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveImageFromProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Kiểm tra nếu bảng 'product_templates_new' đã tồn tại, nếu có thì xóa
        if (Schema::hasTable('products_new')) {
            Schema::dropIfExists('products');
        }

        // Tạo bảng mới không có cột 'image'
        Schema::create('products_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();

            // Thêm foreign key constraints
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Sao chép dữ liệu từ bảng cũ sang bảng mới (giữ lại các cột khác ngoại trừ 'image')


        // Xóa bảng cũ
        Schema::dropIfExists('products');

        // Đổi tên bảng mới thành tên bảng cũ
        Schema::rename('products_new', 'products');
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kiểm tra nếu bảng 'product_templates_new' đã tồn tại, nếu có thì xóa
        if (Schema::hasTable('products_new')) {
            Schema::dropIfExists('products_new');
        }

        // Tạo lại bảng với cột 'image' đã bị xóa
        Schema::create('products_new', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('category_id');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->unsignedBigInteger('template_id')->nullable();
            $table->tinyInteger('status')->default(1);
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->timestamps();

            // Thêm foreign key constraints
            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Sao chép lại dữ liệu từ bảng cũ sang bảng mới
        

        // Xóa bảng cũ
        Schema::dropIfExists('products');

        // Đổi tên bảng mới thành tên bảng cũ
        Schema::rename('products_new', 'products');
    }
}
