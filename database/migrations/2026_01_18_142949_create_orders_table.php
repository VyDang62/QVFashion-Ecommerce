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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('order_code')->unique();
            $table->timestamp('order_date')->useCurrent();
            $table->decimal('total_cost', 15, 2);
            $table->foreignId('voucher_id')->nullable()->after('user_id')->constrained('vouchers')->onDelete('set null');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('total_cost');
            $table->decimal('final_amount', 15, 2)->nullable()->after('discount_amount');
            $table->foreignId('user_id')->nullable() ->constrained('users')->nullOnDelete();
            $table->integer('order_status');
            $table->string('shipping_address_detail');
            $table->string('shipping_ward');
            $table->string('shipping_ward_code');
            $table->string('shipping_district');
            $table->integer('shipping_district_id');
            $table->string('shipping_province');
            $table->integer('shipping_province_id');
            $table->string('shipping_phone_number');
            $table->string('shipping_recipient_name');
            $table->decimal('shipping_fee', 15, 2)->nullable();
            $table->text('order_note')->nullable();
            $table->string('payment_method');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
