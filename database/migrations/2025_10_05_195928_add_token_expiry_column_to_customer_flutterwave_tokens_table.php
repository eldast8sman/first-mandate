<?php

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
        Schema::table('customer_flutterwave_tokens', function (Blueprint $table) {
            $table->date('token_expiry')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_flutterwave_tokens', function (Blueprint $table) {
            $table->dropColumn('token_expiry');
        });
    }
};
