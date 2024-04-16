<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionApproversTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_approvers', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_id')->index();
            $table->string('form_type')->index();
            $table->string('batch');
            $table->string('label');
            $table->integer('subunit_id')->nullable()->index();
            $table->integer('employee_id')->index();
            $table->string('action');
            $table->integer('level');
            $table->integer('receiver_id')->nullable()->index();
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
        Schema::dropIfExists('transaction_approvers');
    }
}
