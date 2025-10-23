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
        Schema::create('electricity_bill_payments', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignIdFor(User::class, 'user_id')->constrained()->onDelete('cascade');
            $table->string('platform')->default('Flutterwave');
            $table->string('biller');
            $table->string('biller_code');
            $table->string('billing_product');
            $table->string('billing_product_code');
            $table->string('customer_identifier');
            $table->string('customer_name');
            $table->decimal('amount', 15, 2);
            $table->decimal('charges', 15, 2)->default(0);
            $table->string('transaction_reference')->unique()->nullable();
            $table->text('request')->nullable();
            $table->text('response')->nullable();
            $table->text('response2')->nullable();
            $table->string('reference')->unique();
            $table->string('token')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('electricity_bill_payments');
    }
};
