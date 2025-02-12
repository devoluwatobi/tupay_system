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
        Schema::create('r_m_b_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('amount', 20, 2);
            $table->integer('type');
            $table->integer('approved_by')->nullable();
            $table->integer('rejected_by')->nullable();
            $table->string('rejected_reason')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('trx_status')->nullable();
            $table->decimal('charge', 10, 2);
            $table->decimal('rate', 10, 2);
            $table->integer('status')->default(0)->comment('0 for pending, 1 for approved, 2 for rejected, 3 for cancelled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('r_m_b_wallet_transactions');
    }
};
