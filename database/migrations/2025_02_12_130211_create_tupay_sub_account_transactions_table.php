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
        Schema::create('tupay_sub_account_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('amount', 20, 2);
            $table->decimal('fees', 20, 2);
            $table->decimal('charge', 20, 2);
            $table->decimal('settlement', 20, 2);
            $table->string('type');
            $table->string('sessionId');
            $table->string('paymentReference');
            $table->string('creditAccountName');
            $table->string('creditAccountNumber');
            $table->string('destinationInstitutionCode');
            $table->string('debitAccountName');
            $table->string('debitAccountNumber');
            $table->string('narration')->nullable();
            $table->string('transaction_status')->nullable();
            $table->integer('status')->default(0)->comment('0 for pending, 1 for approved, 2 for rejected, 3 for cancelled');
            $table->longText('data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tupay_sub_account_transactions');
    }
};
