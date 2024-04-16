<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnnualEvaluationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('annual_evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('employee_id')->index();
            $table->json('measures');
            $table->float('total_grade');
            $table->json('performance_discussion');
            $table->string('attachment')->nullable();
            $table->string('assessment')->nullable();
            $table->string('assessment_mark')->nullable();
            $table->string('form_type')->index();
            $table->integer('level');
            $table->string('current_status');
            $table->string('current_status_mark');
            $table->integer('requestor_id')->index();
            $table->string('requestor_remarks')->nullable();
            $table->string('date_evaluated')->index();
            $table->string('is_fulfilled');
            $table->string('date_fulfilled')->nullable()->index();
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
        Schema::dropIfExists('annual_evaluations');
    }
}
