<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDaFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('da_forms', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->integer('datachange_id')->index();
            $table->date('inclusive_date_start')->index();
            $table->date('inclusive_date_end')->index();
            $table->string('form_type')->index();
            $table->integer('level');
            $table->string('current_status');
            $table->string('current_status_mark');
            $table->integer('requestor_id')->index();
            $table->string('requestor_remarks')->nullable();
            $table->string('is_fulfilled');
            $table->date('date_fulfilled')->nullable()->index();
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
        Schema::dropIfExists('da_forms');
    }
}
