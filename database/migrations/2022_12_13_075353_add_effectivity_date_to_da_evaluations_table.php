<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEffectivityDateToDaEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('da_evaluations', function (Blueprint $table) {
            // $table->date('effectivity_date')->after('requestor_remarks')->nullable();
            $table->decimal('salary', 19, 2)->after('effectivity_date')->nullable();
            $table->decimal('job_rate', 19, 2)->after('effectivity_date')->nullable();
            $table->decimal('allowance', 19, 2)->after('effectivity_date')->nullable();
            $table->string('additional_rate')->after('effectivity_date')->nullable();
            $table->string('job_level')->after('effectivity_date')->nullable();
            $table->string('salary_structure')->after('effectivity_date')->nullable();
            $table->string('jobrate_name')->after('effectivity_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('da_evaluations', function (Blueprint $table) {
            //
        });
    }
}
