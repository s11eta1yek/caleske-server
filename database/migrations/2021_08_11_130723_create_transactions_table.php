<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_id')->nullable()->constrained('travels');
            $table->foreignId('user_id')->constrained('users');
            $table->enum('type', [
                'driver_charge',
                'driver_clear',
                'user_charge',
                'user_clear',
                'travel_cost',
                'travel_income'
            ]);
            $table->integer('amount');
            $table->enum('status', ['pending', 'freeze', 'success', 'failed']);
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
        Schema::dropIfExists('transactions');
    }
}
