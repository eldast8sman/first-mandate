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
        Schema::create('due_dates', function (Blueprint $table) {
            $table->id();
            $table->integer('landlord_id')->nullable();
            $table->integer('property_tenant_id')->nullable();
            $table->integer('property_id')->nullable();
            $table->integer('property_unit_id')->nullable();
            $table->integer('property_manager_id')->nullable();
            $table->date('due_date');
            $table->string('purpose');
            $table->text('remarks')->nullable();
            $table->boolean('cash_payment')->default(0);
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('due_dates');
    }
};
