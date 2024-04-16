<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('employee_statuses', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->string('employment_type_label');
            $table->string('employment_type');
            $table->string('employment_date_start')->nullable();
            $table->string('employment_date_end')->nullable();
            $table->string('regularization_date')->nullable();
            $table->string('hired_date')->nullable()->index();
            $table->date('hired_date_fix')->nullable()->index();
            $table->date('reminder')->nullable()->index();
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
        Schema::dropIfExists('employee_statuses');
    }
}
