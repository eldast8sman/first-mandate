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
        Schema::table('properties', function (Blueprint $table) {
            $table->integer('landlord_id')->nullable()->change();
        });
        Schema::table('property_units', function (Blueprint $table) {
            $table->integer('landlord_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'landlord_id')->nullable()->change();
        });
        Schema::table('property_units', function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'landlord_id')->nullable()->change();
        });
    }
};
