<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->unique()->nullable();
            $table->boolean('email_verified')->default(0);
            $table->string('password')->nullable();
            $table->string('verification_token')->nullable();
            $table->dateTime('verification_token_expiry')->nullable();
            $table->string('token')->nullable();
            $table->dateTime('token_expiry')->nullable();
            $table->integer('status')->default(1);
            $table->string('section')->nullable();
            $table->timestamp('last_login')->nullable();
            $table->timestamp('prev_login')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
