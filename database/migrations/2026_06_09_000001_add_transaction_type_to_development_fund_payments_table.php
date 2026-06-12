<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('development_fund_payments', function (Blueprint $table) {
            $table->enum('transaction_type', ['income', 'expense'])
                ->default('income')
                ->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('development_fund_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_type');
        });
    }
};
