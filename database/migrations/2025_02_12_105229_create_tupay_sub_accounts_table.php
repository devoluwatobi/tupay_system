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
        Schema::create('tupay_sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('external_id');
            $table->string('provider')->default('safehaven');
            $table->string('accountProduct')->nullable();
            $table->string('accountNumber');
            $table->string('accountName');
            $table->string('accountType')->nullable();
            $table->string('currencyCode');
            $table->string('bvn')->nullable();
            $table->string('nin')->nullable();
            $table->string('accountBalance')->nullable();
            $table->string('external_status')->default('Active');
            $table->string('callbackUrl')->nullable();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('emailAddress');
            $table->string('subAccountType')->default('Individual');
            $table->string('externalReference');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tupay_sub_accounts');
    }
};
