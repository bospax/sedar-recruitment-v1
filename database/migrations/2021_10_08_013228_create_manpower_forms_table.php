<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateManpowerFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('manpower_forms', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->integer('position_id')->index();
            $table->integer('jobrate_id')->index();
            $table->integer('manpower_count');
            $table->string('employment_type');
            $table->string('employment_type_label');
            $table->string('requisition_type');
            $table->string('requisition_type_mark');
            $table->string('attachment')->nullable();
            $table->text('justification')->nullable();
            $table->integer('replacement_for')->nullable()->index();
            $table->string('form_type')->index();
            $table->integer('level');
            $table->string('current_status');
            $table->string('current_status_mark');
            $table->integer('requestor_id')->index();
            $table->string('requestor_remarks')->nullable();
            $table->string('is_fulfilled');
            $table->date('date_fulfilled')->nullable()->index();
            $table->integer('tobe_hired')->index();
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
        Schema::dropIfExists('manpower_forms');
    }
}
