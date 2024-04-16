<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormApproversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_approvers', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id');
            $table->integer('form_setting_id');
            $table->integer('level');
            $table->string('action');
            $table->string('approved_mark');
            $table->string('rejected_mark');
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
        Schema::dropIfExists('form_approvers');
    }
}
