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
        Schema::create('user_fund_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->string("account_name");
            $table->string("account_no");
            $table->string("bank_name");
            $table->string("auto_settlement");
            $table->string("reference");
            $table->integer("status")->default(1);
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
        Schema::dropIfExists('user_fund_bank_accounts');
    }
};
