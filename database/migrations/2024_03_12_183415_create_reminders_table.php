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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->integer('due_date_id')->nullable();
            $table->string('user_type');
            $table->integer('user_id');
            $table->string('recipient_type');
            $table->integer('recipient_id');
            $table->string('reminder_type');
            $table->text('short_description')->nullable();
            $table->string('frequency_type')->default('one_time');
            $table->string('recurring_type')->nullable();
            $table->date('next_reminder_date');
            $table->integer('reminder_time')->nullable();
            $table->integer('recurring_limit')->default(1);
            $table->integer('total_sent')->default(0);
            $table->string('receiving_medium')->default('email');
            $table->boolean('money_reminder')->default(0);
            $table->double('amount')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminders');
    }
};
