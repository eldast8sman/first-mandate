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
        Schema::create('flutterwave_webhooks', function (Blueprint $table) {
            $table->id();
            $table->longText('webhook');
            $table->integer('user_id')->nullable();
            $table->string('event')->nullable();
            $table->string('trans_reference')->nullable();
            $table->double('amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flutterwave_webhooks');
    }
};
