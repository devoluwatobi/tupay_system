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
        Schema::create('r_m_b_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('logo');
            $table->decimal('charge', 10, 2);
            $table->integer('status')->default(1)->comment('0 => Inactive, 1 => Active');
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
        Schema::dropIfExists('r_m_b_payment_methods');
    }
};
