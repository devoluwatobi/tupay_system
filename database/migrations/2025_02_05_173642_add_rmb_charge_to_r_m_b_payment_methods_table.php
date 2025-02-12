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
        Schema::table('r_m_b_payment_methods', function (Blueprint $table) {
            $table->decimal('rmb_charge', 10, 2)->default(0.5);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('r_m_b_payment_methods', function (Blueprint $table) {
            $table->dropColumn('rmb_charge');
        });
    }
};
