<?php

use App\Models\User;
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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->foreignIdFor(User::class, 'landlord_id');
            $table->string('title');
            $table->string('property_type')->default('residential');
            $table->string('building_type')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('state');
            $table->string('country')->default('Nigeria');
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
