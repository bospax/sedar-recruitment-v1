<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->integer('employee_id')->index();
            $table->string('region')->index();
            $table->string('province')->index();
            $table->string('municipal')->index();
            $table->string('barangay')->index();
            $table->string('street')->nullable();
            $table->string('zip_code')->nullable();
            $table->text('detailed_address')->nullable();
            $table->text('foreign_address')->nullable();
            $table->text('address_remarks')->nullable();
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
        Schema::dropIfExists('addresses');
    }
}
