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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type'); // Loại thông báo (low_stock, 'new_order', 'system_update')
            $table->morphs('notifiable'); // user_id và user_type (để gửi cho Admin/User)
            $table->jsonb('data'); // Chứa thông tin chi tiết: id sp, tên sp, số lượng còn lại
            $table->timestamp('read_at')->nullable(); // Đánh dấu đã đọc
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
