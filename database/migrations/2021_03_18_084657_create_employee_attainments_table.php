<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeAttainmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_attainments', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->string('attainment');
            $table->string('course');
            $table->string('degree');
            $table->string('institution')->nullable();
            $table->string('attachment')->nullable();
            $table->string('honorary')->nullable();
            $table->string('academic_year_from')->nullable();
            $table->string('academic_year_to')->nullable();
            $table->string('years')->nullable();
            $table->string('gpa')->nullable();
            $table->text('attainment_remarks')->nullable();
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
        Schema::dropIfExists('employee_attainments');
    }
}
