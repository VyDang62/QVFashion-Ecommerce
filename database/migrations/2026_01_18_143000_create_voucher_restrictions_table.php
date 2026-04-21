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
        Schema::create('voucher_restrictions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('voucher_id')->constrained('vouchers')->onDelete('cascade');
        $table->string('restrict_type'); 
        $table->unsignedBigInteger('restrict_id'); 
        $table->timestamps();
        $table->index(['restrict_type', 'restrict_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
