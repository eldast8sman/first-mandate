<?php

use App\Models\PropertyTenant;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rent_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PropertyTenant::class, 'tenancy_id')->constrained('property_tenants')->onDelete('cascade');
            $table->decimal('rent_amount', 20, 2);
            $table->string('payment_type');
            $table->integer('no_of_installments')->nullable();
            $table->decimal('installment_amount', 20, 2)->nullable();
            $table->integer('installments_paid')->default(0);
            $table->date('next_due_date')->nullable();
            $table->date('rent_start_date')->nullable();
            $table->date('rent_end_date')->nullable();
            $table->integer('payment_status')->default(0)->comment('0 = pending, 1 = paid, 2 = failed, 3 = partially paid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_payments');
    }
};
