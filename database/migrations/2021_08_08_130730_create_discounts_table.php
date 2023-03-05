<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('code', 100)->unique();
            $table->tinyInteger('percentage');
            $table->integer('max_discount');
            $table->tinyInteger('max_usage_number');
            $table->tinyInteger('used_times_number');
            $table->tinyInteger('has_finished');
            $table->date('expire_at');
            $table->timestamps();

            $table->index(['expire_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discounts');
    }
}
