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
        Schema::create('safe_verifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('safe_id');
            $table->string('type')->comment("BVN & NIN");
            $table->string('value');
            $table->integer('status')->default(0)->comment('0 => pending, 1 => success, 2 => failed');
            $table->string('otp_id');
            $table->longText('request_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('safe_verifications');
    }
};
