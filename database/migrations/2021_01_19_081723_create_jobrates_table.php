<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobratesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobrates', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('position_id')->nullable();
            $table->string('job_level');
            $table->string('job_rate');
            $table->string('salary_structure');
            $table->string('jobrate_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('jobrates');
    }
}
