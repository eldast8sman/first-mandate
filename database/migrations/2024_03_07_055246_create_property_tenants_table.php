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
            $table->integer('user_id')->nullable();
            $table->foreignIdFor(User::class, 'landlord_id');
            $table->foreignIdFor(Property::class, 'property_id');
            $table->foreignIdFor(PropertyUnit::class, 'property_unit_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('current_tenant')->default(1);
            $table->date('lease_start');
            $table->date('lease_end');
            $table->string('rent_term')->nullable();
            $table->double('rent_amount')->nullable();
            $table->date('rent_due_date')->nullable();
            $table->string('rent_payment_status');
            $table->string('payment_type')->default('one_time');
            $table->integer('no_of_installments')->default(1);
            $table->double('installment_amount')->nullable();
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
