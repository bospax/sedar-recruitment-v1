<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeDatachangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_datachanges', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('employee_id')->index();

            $table->integer('current_position_id')->nullable();
            $table->integer('current_department_id')->nullable();
            $table->integer('current_subunit_id')->nullable();
            $table->integer('current_location_id')->nullable();
            $table->integer('current_company_id')->nullable();
            $table->integer('current_division_id')->nullable();
            $table->integer('current_division_cat_id')->nullable();
            $table->integer('new_position_id')->nullable();
            $table->integer('new_department_id')->nullable();
            $table->integer('new_subunit_id')->nullable();
            $table->integer('new_location_id')->nullable();
            $table->integer('new_company_id')->nullable();
            $table->integer('new_division_id')->nullable();
            $table->integer('new_division_cat_id')->nullable();

            $table->string('current_jobrate_name')->nullable();
            $table->string('current_salary_structure')->nullable();
            $table->string('current_job_level')->nullable();
            $table->string('current_additional_rate')->nullable();
            $table->decimal('current_allowance', 19, 2)->nullable();
            $table->decimal('current_job_rate', 19, 2)->nullable();
            $table->decimal('current_salary', 19, 2)->nullable();
            $table->string('new_jobrate_name')->nullable();
            $table->string('new_salary_structure')->nullable();
            $table->string('new_job_level')->nullable();
            $table->string('new_additional_rate')->nullable();
            $table->decimal('new_allowance', 19, 2)->nullable();
            $table->decimal('new_job_rate', 19, 2)->nullable();
            $table->decimal('new_salary', 19, 2)->nullable();

            $table->string('change_reason')->nullable();
            $table->string('attachment')->nullable();
            $table->string('form_type')->index();
            $table->integer('level');
            $table->string('current_status');
            $table->string('current_status_mark');
            $table->integer('requestor_id')->index();
            $table->string('requestor_remarks')->nullable();
            $table->string('is_fulfilled')->index();
            $table->string('date_fulfilled')->nullable()->index();
            $table->date('effectivity_date')->nullable();
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
        Schema::dropIfExists('employee_datachanges');
    }
}
