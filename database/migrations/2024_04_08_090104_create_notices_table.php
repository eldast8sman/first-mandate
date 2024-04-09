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
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->string('sender_type');
            $table->integer('sender_id');
            $table->string('receiver_type')->default('tenant');
            $table->integer('receiver_id');
            $table->integer('tenant_id')->nullable();
            $table->string('type');
            $table->text('description');
            $table->date('notice_date')->nullable();
            $table->string('notice_time')->nullable();
            $table->string('acknowledged_status')->default('pending');
            $table->text('remarks')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notices');
    }
};
