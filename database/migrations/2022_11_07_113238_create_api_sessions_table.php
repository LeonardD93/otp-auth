<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('token')->unique();
            $table->timestamp('expired_at', $precision = 0)->nullable();
            $table->string('otp_code')->nullable();
            $table->boolean('otp_confirmed')->default(false);
            $table->timestamp('otp_expire', $precision = 0)->nullable();
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
        Schema::dropIfExists('api_sessions');
    }
};
