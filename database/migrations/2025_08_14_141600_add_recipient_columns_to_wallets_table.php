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
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('rec_bank')->nullable()->after('account_name');
            $table->string('rec_account_number')->nullable()->after('rec_bank');
            $table->string('rec_account_name')->nullable()->after('rec_account_number');
            $table->string('bvn')->nullable()->after('uuid');
            $table->string('tx_ref')->nullable()->after('bvn');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['rec_bank', 'rec_account_number', 'rec_account_name', 'bvn', 'tx_ref']);
        });
    }
};
