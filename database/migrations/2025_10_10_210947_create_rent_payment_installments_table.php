<?php

use App\Models\RentPayment;
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
        Schema::create('rent_payment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(RentPayment::class, 'rent_payment_id')->constrained('rent_payments')->onDelete('cascade');
            $table->string('payment_method');
            $table->decimal('amount', 20, 2);
            $table->integer('no_of_installment');
            $table->integer('status')->default(0)->comment('0 = pending, 1 = paid, 2 = failed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_payment_installments');
    }
};
