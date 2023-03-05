<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTravelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('travels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->foreignId('driver_car_id')->nullable()->constrained('driver_cars');
            $table->foreignId('language_id')->nullable()->constrained('languages');
            $table->foreignId('discount_id')->nullable()->constrained('discounts');
            $table->foreignId('canceled_by_id')->nullable()->constrained('users');
            $table->integer('price');
            $table->integer('discount_price');
            $table->integer('final_price');
            $table->enum('request_type', ['private', 'public']);
            $table->enum('payment_type', ['cash', 'online']);
            $table->enum('payment_status', ['pending', 'success', 'failed']);
            $table->enum('status', [
                'no_driver',
                'not_started',
                'started',
                'finished',
                'failed'
            ]);
            $table->tinyInteger('is_finished');
            $table->timestamp('travel_start_time')->nullable()->default(null);
            $table->timestamp('travel_end_time')->nullable()->default(null);
            $table->tinyInteger('is_reserve');
            $table->datetime('reserve_time')->nullable()->default(null);
            $table->enum('car_type', ['samand', 'peugeot', 'lux', 'all']);
            $table->json('travel_options');
            $table->string('description', 255);
            $table->timestamps();

            $table->index(['reserve_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('travels');
    }
}
