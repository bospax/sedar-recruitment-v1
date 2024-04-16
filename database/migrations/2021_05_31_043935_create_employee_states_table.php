<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeStatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_states', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->string('employee_state_label');
            $table->string('employee_state');
            $table->string('state_date_start')->nullable()->index();
            $table->string('state_date_end')->nullable()->index();
            $table->string('state_date')->nullable()->index();
            $table->string('attachment')->nullable();
            $table->string('status_remarks')->nullable();
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
        Schema::dropIfExists('employee_states');
    }
}
