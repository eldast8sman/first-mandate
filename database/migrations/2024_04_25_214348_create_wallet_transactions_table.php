<?php

use App\Models\User;
use App\Models\Wallet;
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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class, 'user_id');
            $table->foreignIdFor(Wallet::class, 'wallet_id');
            $table->string('type');
            $table->decimal('original_amount', 20, 2);
            $table->decimal('charges', 20, 2)->default(0);
            $table->decimal('amount', 20, 2);
            $table->decimal('pre_amount', 20, 2)->nullable();
            $table->decimal('post_amount', 20, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
