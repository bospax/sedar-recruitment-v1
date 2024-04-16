<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalaryStructureToDatachangeFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('datachange_forms', function (Blueprint $table) {
            $table->decimal('salary', 19, 2)->after('prev_department_id')->nullable();
            $table->decimal('job_rate', 19, 2)->after('prev_department_id')->nullable();
            $table->decimal('allowance', 19, 2)->after('prev_department_id')->nullable();
            $table->string('additional_rate')->after('prev_department_id')->nullable();
            $table->string('job_level')->after('prev_department_id')->nullable();
            $table->string('salary_structure')->after('prev_department_id')->nullable();
            $table->string('jobrate_name')->after('prev_department_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('datachange_forms', function (Blueprint $table) {
            //
        });
    }
}
