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
        Schema::table('bank_details', function (Blueprint $table) {
            $table->string('safehaven_name_id')->nullable();
            $table->string('safehaven_bank_code')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bank_details', function (Blueprint $table) {
            $table->dropColumn('safehaven_name_id');
            $table->dropColumn('safehaven_bank_code');
        });
    }
};
