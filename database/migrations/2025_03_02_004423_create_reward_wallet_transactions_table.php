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
        Schema::create('reward_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 10, 2);
            $table->integer('user_id');
            $table->string('type')->comment('0 for withdrawal, 1 for trx_reward, 2 for referral, 3 for other');
            $table->string('referred_user_id')->nullable();
            $table->integer('status')->default(0)->comment('0 for pending, 1 for completed, 2 for failed, 3 for cancelled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_wallet_transactions');
    }
};
