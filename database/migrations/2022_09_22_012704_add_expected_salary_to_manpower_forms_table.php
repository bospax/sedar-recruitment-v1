<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddExpectedSalaryToManpowerFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manpower_forms', function (Blueprint $table) {
            $table->string('expected_salary')->after('jobrate_id')->nullable();
            $table->string('salary_structure')->after('jobrate_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('manpower_forms', function (Blueprint $table) {
            //
        });
    }
}
