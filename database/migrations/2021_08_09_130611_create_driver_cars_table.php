<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDriverCarsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('driver_cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('car_id')->constrained('cars');
            $table->enum('plate_type', ['private', 'taxi', 'public']);
            $table->tinyInteger('plate_two_numbers');
            $table->string('plate_letter', 127);
            $table->integer('plate_three_numbers');
            $table->tinyInteger('plate_city_code');
            $table->string('color', 100);
            $table->string('image', 100)->nullable()->default(null);
            $table->enum('status', ['pending', 'confirmed', 'rejected', 'canceled']);
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
        Schema::dropIfExists('driver_cars');
    }
}
