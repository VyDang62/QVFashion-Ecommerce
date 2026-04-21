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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Loại thông báo ('low_stock', 'new_order', 'system_update')
            $table->string('notification_type'); 
            
            // Kênh nhận (VD: 'database', 'mail', 'broadcast')
            // Nếu bạn chỉ cần Bật/Tắt chung thì có thể bỏ cột channel
            $table->string('channel')->default('database'); 
            
            $table->boolean('is_enabled')->default(true);
            
            $table->timestamps();

            // Đảm bảo một user không bị trùng lặp cài đặt cho cùng một loại và kênh
            $table->unique(['user_id', 'notification_type', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
