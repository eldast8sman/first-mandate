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
        Schema::create('customer_flutterwave_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->string('first_digits');
            $table->string('last_digits');
            $table->string('card_issuer');
            $table->string('card_type');
            $table->string('card_expiry');
            $table->string('token');
            $table->string('country')->default('Nigeria NG');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_flutterwave_tokens');
    }
};
