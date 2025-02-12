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
        Schema::table('safe_verifications', function (Blueprint $table) {
            $table->string('firstName')->nullable();
            $table->string('middleName')->nullable();
            $table->string('lastName')->nullable();
            $table->string('dateOfBirth')->nullable();
            $table->string('phoneNumber1')->nullable();
            $table->string('phoneNumber2')->nullable();
            $table->string('gender')->nullable();
            $table->string('enrollmentBank')->nullable();
            $table->string('enrollmentBranch')->nullable();
            $table->string('email')->nullable();
            $table->string('lgaOfOrigin')->nullable();
            $table->string('lgaOfResidence')->nullable();
            $table->string('maritalStatus')->nullable();
            $table->string('nationality')->nullable();
            $table->string('residentialAddress')->nullable();
            $table->string('stateOfOrigin')->nullable();
            $table->string('stateOfResidence')->nullable();
            $table->string('title')->nullable();
            $table->string('watchListed')->nullable();
            $table->string('levelOfAccount')->nullable();
            $table->string('registrationDate')->nullable();
            $table->longText('imageBase64')->nullable();
            $table->longText('validation_data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('safe_verifications', function (Blueprint $table) {
            $table->dropColumn('firstName');
            $table->dropColumn('middleName');
            $table->dropColumn('lastName');
            $table->dropColumn('dateOfBirth');
            $table->dropColumn('phoneNumber1');
            $table->dropColumn('phoneNumber2');
            $table->dropColumn('gender');
            $table->dropColumn('enrollmentBank');
            $table->dropColumn('enrollmentBranch');
            $table->dropColumn('email');
            $table->dropColumn('lgaOfOrigin');
            $table->dropColumn('lgaOfResidence');
            $table->dropColumn('maritalStatus');
            $table->dropColumn('nationality');
            $table->dropColumn('residentialAddress');
            $table->dropColumn('stateOfOrigin');
            $table->dropColumn('stateOfResidence');
            $table->dropColumn('title');
            $table->dropColumn('watchListed');
            $table->dropColumn('levelOfAccount');
            $table->dropColumn('registrationDate');
            $table->dropColumn('imageBase64');
        });
    }
};
