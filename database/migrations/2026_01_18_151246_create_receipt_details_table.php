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
        Schema::create('receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained('goods_receipts')->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained('product_variants');
            $table->smallInteger('received_quantity');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('sub_total', 15, 2);
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('receipt_details');
    }
};
