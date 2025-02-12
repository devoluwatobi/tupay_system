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
        Schema::table('r_m_b_transactions', function (Blueprint $table) {
            $table->string('paid_with')->default('ngn')->comment("ngn or rmb");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('r_m_b_transactions', function (Blueprint $table) {
            $table->dropColumn('paid_with');
        });
    }
};
