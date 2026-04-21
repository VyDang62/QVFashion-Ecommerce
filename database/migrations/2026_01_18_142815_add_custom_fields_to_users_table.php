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
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('name', 'full_name');
            $table->string('phone_number')->nullable();
            $table->text('address_detail')->nullable();
            $table->string('ward')->nullable();
            $table->string('ward_code')->nullable();
            $table->string('district')->nullable();
            $table->integer('district_id')->nullable();
            $table->string('province')->nullable();
            $table->integer('province_id')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
