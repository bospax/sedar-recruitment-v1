<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_positions', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index()->unique();
            $table->integer('position_id')->index();
            $table->integer('jobrate_id')->index()->nullable();
            $table->string('additional_rate')->nullable();
            $table->string('additional_tool')->nullable();
            $table->string('schedule')->nullable();
            $table->string('emp_shift')->nullable();
            $table->string('remarks')->nullable();
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
        Schema::dropIfExists('employee_positions');
    }
}
