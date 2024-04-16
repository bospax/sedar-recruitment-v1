<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobLevelToManpowerFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('manpower_forms', function (Blueprint $table) {
            $table->string('job_level')->after('salary_structure')->nullable();
            $table->string('jobrate_name')->after('salary_structure')->nullable();
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
