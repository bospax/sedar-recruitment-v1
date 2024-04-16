<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('department_id')->index();
            $table->integer('subunit_id')->index();
            $table->integer('jobband_id')->index();
            $table->string('position_name');
            $table->string('status');
            $table->string('status_description');
            $table->string('payrate');
            $table->string('employment')->nullable();
            $table->integer('no_of_months')->nullable();
            $table->string('schedule')->nullable();
            $table->string('shift');
            $table->string('team');
            $table->string('job_profile')->nullable();
            $table->string('attachments')->nullable();
            $table->string('tools')->nullable();
            $table->integer('superior')->nullable()->index();
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
        Schema::dropIfExists('positions');
    }
}
