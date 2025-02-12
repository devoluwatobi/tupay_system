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
        Schema::create('utility_bill_transactions', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->decimal('amount');
            $table->string('number')->nullable();
            $table->integer('utility_id');
            $table->string('type')->nullable();
            $table->string('package')->nullable();
            $table->string('service_icon')->nullable();
            $table->string('service_name')->nullable();
            $table->string('transaction_ref')->nullable();
            $table->string('token')->nullable()->comment("token for electricity and any one that needs");
            $table->string('trx_status')->default("Approved")->comment("transaction status from provider");
            $table->integer('status')->default(1)->comment('0 for pending, 1 for completed, 2 for failed');
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
        Schema::dropIfExists('utility_bill_transactions');
    }
};
