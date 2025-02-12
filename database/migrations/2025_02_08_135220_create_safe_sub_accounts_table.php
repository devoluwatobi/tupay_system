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
        Schema::create('safe_sub_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('id_safe_id');
            $table->string('id_type')->comment("BVN & NIN");
            $table->string('id_value');
            $table->integer('id_status')->default(0)->comment('0 => pending, 1 => success, 2 => failed');
            $table->integer('status')->default(0)->comment('0 => pending, 1 => success, 2 => failed');
            $table->string('otp_id');
            $table->longText('id_request_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safe_sub_accounts');
    }
};
