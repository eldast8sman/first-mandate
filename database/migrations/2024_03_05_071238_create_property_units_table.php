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
        Schema::create('property_units', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Property::class, 'property_id');
            $table->foreignIdFor(User::class, 'landlord_id');
            $table->string('unit_name')->default('Flat');
            $table->integer('no_of_bedrooms')->default(1);
            $table->string('occupation_status');
            $table->double('annual_rent')->nullable();
            $table->timestamps();
        });
    }

    /**ise
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_units');
    }
};
