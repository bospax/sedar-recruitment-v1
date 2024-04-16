<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('prefix_id');
            $table->integer('id_number');
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('suffix')->nullable();
            $table->string('birthdate');
            $table->integer('age')->nullable();
            $table->string('religion');
            $table->string('civil_status');
            $table->string('gender');
            $table->string('image')->nullable();
            $table->integer('referrer_id')->nullable();
            $table->string('form_type');
            $table->integer('level');
            $table->string('current_status');
            $table->string('current_status_mark');
            $table->integer('requestor_id');
            $table->text('remarks')->nullable();
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
        Schema::dropIfExists('employees');
    }
}
