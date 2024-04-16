<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFormHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('form_history', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->integer('form_id')->index();
            $table->string('form_type')->index();
            $table->json('form_data');
            $table->string('status');
            $table->string('status_mark');
            $table->integer('reviewer_id')->nullable()->index();
            $table->string('review_date')->nullable();
            $table->string('reviewer_attachment')->nullable();
            $table->string('reviewer_action')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('level');
            $table->integer('requestor_id')->index();
            $table->integer('employee_id')->nullable()->index();
            $table->string('is_fulfilled');
            $table->string('date_fulfilled')->nullable();
            $table->string('description');
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
        Schema::dropIfExists('form_history');
    }
}
