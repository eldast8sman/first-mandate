<?php

use App\Models\Property;
use App\Models\PropertyUnit;
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
        Schema::create('property_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('uuid');
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(User::class, 'landlord_id');
            $table->foreignIdFor(Property::class, 'property_id');
            $table->foreignIdFor(PropertyUnit::class, 'property_unit_id');
            $table->string('name');
            $table->string('email');
            $table->string('phone');
            $table->boolean('current_tenant');
            $table->date('lease_start');
            $table->date('lease_end');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_tenants');
    }
};
