<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobratesToEmployeePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_positions', function (Blueprint $table) {
            $table->decimal('salary', 19, 2)->after('additional_rate')->nullable();
            $table->decimal('job_rate', 19, 2)->after('additional_rate')->nullable();
            $table->decimal('allowance', 19, 2)->after('additional_rate')->nullable();
            $table->string('job_level')->after('additional_rate')->nullable();
            $table->string('salary_structure')->after('additional_rate')->nullable();
            $table->string('jobrate_name')->after('additional_rate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('employee_positions', function (Blueprint $table) {
            //
        });
    }
}
