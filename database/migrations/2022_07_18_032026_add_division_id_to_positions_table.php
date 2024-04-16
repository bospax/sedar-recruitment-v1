<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDivisionIdToPositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('employee_positions', function (Blueprint $table) {
            $table->integer('division_id')->index()->after('jobrate_id')->nullable();
            $table->integer('division_cat_id')->index()->after('jobrate_id')->nullable();
            $table->integer('company_id')->index()->after('jobrate_id')->nullable();
            $table->integer('location_id')->index()->after('jobrate_id')->nullable();
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
