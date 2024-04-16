<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatachangeFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datachange_forms', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->integer('change_position_id')->index();
            $table->string('change_reason');
            $table->string('for_da')->nullable();
            $table->integer('manpower_id')->index();
            $table->integer('prev_position_id')->nullable()->index();
            $table->integer('prev_department_id')->nullable()->index();
            $table->string('form_type')->index();
            $table->integer('level');
            $table->string('current_status');
            $table->string('current_status_mark');
            $table->integer('requestor_id')->index();
            $table->string('requestor_remarks')->nullable();
            $table->string('is_fulfilled');
            $table->date('date_fulfilled')->nullable()->index();
            $table->timestamps();
            $table->index(['created_at', 'updated_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('datachange_forms');
    }
}
