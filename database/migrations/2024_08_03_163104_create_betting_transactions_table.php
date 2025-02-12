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
        Schema::create('betting_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('amount', 10, 2);
            $table->string('reference')->nullable();
            $table->string('product')->nullable();
            $table->decimal('charge', 10, 2);
            $table->string('customer_id')->nullable();
            $table->string('first_name')->nullable();
            $table->string('surname')->nullable();
            $table->string('username')->nullable();
            $table->string('date')->nullable();
            $table->string('bet_status')->nullable();
            $table->integer('status')->default(0)->comment('0 for pending, 1 for approved, 2 for rejected, 3 for cancelled');
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
        Schema::dropIfExists('betting_transactions');
    }
};
