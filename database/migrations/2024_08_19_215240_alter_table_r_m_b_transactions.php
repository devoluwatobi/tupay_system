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
        Schema::table('r_m_b_transactions', function (Blueprint $table) {
            $table->integer('recipient_id')->nullable(true)->change();
            $table->integer('recipient_name')->nullable(true)->change();
            $table->longText('qrCode')->nullable();
            $table->longText('account_details')->nullable();
            $table->longText('remark')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('r_m_b_transactions', function (Blueprint $table) {
            $table->integer('recipient_id')->nullable(false)->change();
            $table->integer('recipient_id')->nullable(false)->change();
            $table->dropColumn('qr_code');
            $table->dropColumn('account_details');
            $table->dropColumn('remark');
        });
    }
};
