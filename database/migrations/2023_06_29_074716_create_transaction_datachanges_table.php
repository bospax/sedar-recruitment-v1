<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionDatachangesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_datachanges', function (Blueprint $table) {
            $table->id();
            $table->integer('transaction_id')->nullable()->index();
            $table->string('form_type')->nullable()->index();
            $table->integer('employee_id')->nullable()->index();
            $table->integer('position_id')->nullable()->index();
            $table->integer('department_id')->nullable()->index();
            $table->integer('subunit_id')->nullable()->index();
            $table->integer('division_id')->nullable()->index();
            $table->integer('division_cat_id')->nullable()->index();
            $table->integer('company_id')->nullable()->index();
            $table->integer('location_id')->nullable()->index();
            $table->string('schedule')->nullable();
            $table->string('jobrate_name')->nullable();
            $table->string('salary_structure')->nullable();
            $table->string('job_level')->nullable();
            $table->string('additional_rate')->nullable();
            $table->decimal('allowance', 19, 2)->nullable();
            $table->decimal('job_rate', 19, 2)->nullable();
            $table->decimal('salary', 19, 2)->nullable();
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
        Schema::dropIfExists('transaction_datachanges');
    }
}
