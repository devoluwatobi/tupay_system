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
        Schema::table('tupay_sub_accounts', function (Blueprint $table) {
            $table->longText('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tupay_sub_accounts', function (Blueprint $table) {
            $table->dropColumn('data');
        });
    }
};
