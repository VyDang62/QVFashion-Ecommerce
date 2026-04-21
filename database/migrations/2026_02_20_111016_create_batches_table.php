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
        Schema::create('batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->onDelete('restrict');
            $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->onDelete('restrict');
            $table->string('batch_code')->unique();
            $table->decimal('purchase_price', 15, 2);
            $table->integer('original_quantity');
            $table->integer('remaining_quantity');
            $table->timestamp('received_date')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('batches');
    }
};
