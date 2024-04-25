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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('user_type');
            $table->integer('user_id');
            $table->integer('user_type_id');
            $table->string('type');
            $table->string('trans_reference');
            $table->string('transaction_id');
            $table->double('amount');
            $table->string('currency')->default('NGN');
            $table->string('platform');
            $table->text('request');
            $table->text('response1')->nullable();
            $table->text('response2')->nullable();
            $table->text('response3')->nullable();
            $table->integer('status')->default(0);
            $table->string('event');
            $table->string('event_id')->nullable();
            $table->string('value_given')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
