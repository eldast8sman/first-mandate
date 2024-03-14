<?php

use App\Models\Property;
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
        Schema::create('property_managers', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->foreignIdFor(Property::class, 'property_id');
            $table->foreignIdFor(User::class, 'landlord_id');
            $table->foreignIdFor(User::class, 'manager_id');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->integer('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_managers');
    }
};
