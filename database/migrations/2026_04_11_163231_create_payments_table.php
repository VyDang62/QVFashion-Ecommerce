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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('transaction_no')->nullable(); //Mã giao dịch VNPAY (vnp_TransactionNo)
            $table->string('transaction_reference');      //vnp_TxnRef (Order ID)
            $table->decimal('amount', 15, 2);              //Số tiền (vnp_Amount / 100)
            $table->string('pay_date')->nullable();        //vnp_PayDat
            $table->string('bank_code')->nullable();       //vnp_BankCode
            $table->string('card_type')->nullable();       //vnp_CardType
            $table->string('response_code')->nullable();   //vnp_ResponseCode (00 là thành công)
            $table->text('order_info')->nullable();        //vnp_OrderInfo
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
