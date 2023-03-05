<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('father_name', 100);
            $table->date('birth')->nullable()->default(null);
            $table->enum('gender', ['unknown', 'male', 'female']);
            $table->string('cellphone', 100)->unique();
            $table->string('emergency_phone', 100);
            $table->string('address', 255);
            $table->string('melli_code', 100);
            $table->string('avatar', 100);
            $table->enum('type', ['user', 'driver', 'company', 'admin']);
            $table->string('license_number');
            $table->date('license_expire_at')->nullable()->default(null);
            $table->enum('status', ['pending', 'confirmed', 'rejected']);
            $table->string('confirmation_code', 10);
            $table->timestamp('confirmation_expire_at')->nullable()->default(null);
            $table->string('password');
            $table->rememberToken();
            $table->foreignId('latest_city_id')->nullable()->constrained('cities');
            $table->decimal('latest_latitude', 10, 8)->nullable();
            $table->decimal('latest_longitude', 10, 8)->nullable();
            $table->timestamp('location_updated_at')->nullable()->default(null);
            $table->timestamps();

            $table->index('username');
            $table->index('email');
            $table->index('melli_code');
            $table->index('license_number');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
