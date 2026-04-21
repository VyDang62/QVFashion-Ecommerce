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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); //Người thực hiện
            $table->string('action');       //Hành động: 'create', 'update', 'approve', 'cancel'
            $table->string('model_type');   //Tên Model: 'GoodsReceipt', 'Product'
            $table->unsignedBigInteger('model_id'); //ID của bản ghi bị tác động
            $table->text('description');    //Mô tả chi tiết (ví dụ: "Duyệt phiếu nhập PN-001")
            $table->json('properties')->nullable(); //Lưu dữ liệu cũ/mới nếu cần
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activiy_logs');
    }
};
