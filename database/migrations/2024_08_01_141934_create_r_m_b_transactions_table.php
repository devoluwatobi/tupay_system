<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('r_m_b_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('amount', 20, 2);
            $table->decimal('rate', 12, 2);
            $table->decimal('charge', 10, 2);
            $table->integer('status')->default(0)->comment('0 for pending, 1 for approved, 2 for rejected, 3 for cancelled');
            $table->integer('r_m_b_payment_method_id');
            $table->string('r_m_b_payment_method_title');
            $table->integer('r_m_b_payment_type_id');
            $table->string('r_m_b_payment_type_title');
            $table->string('recipient_id');
            $table->string('recipient_name');
            $table->json('proofs')->comment('data for proof list');
            $table->json('updates')->comment('update for whenever status changes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('r_m_b_transactions');
    }
};
