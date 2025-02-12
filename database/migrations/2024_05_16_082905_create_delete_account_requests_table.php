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
        Schema::create('delete_account_requests', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('reason');
            $table->integer('status')->default('0')->comment("0 for pending, 1 for approved, 2 for rejected");
            $table->integer('updated_by')->nullable();
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
        Schema::dropIfExists('delete_account_requests');
    }
};
