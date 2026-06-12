<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'users',
            'students',
            'teachers',
            'study_classes',
            'student_attendances',
            'financial_categories',
            'tuition_payments',
            'development_fund_payments',
            'asset_categories',
            'assets',
            'notifications',
            'activity_logs',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'tpq_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('tpq_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('tpqs')
                        ->nullOnDelete();
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'activity_logs',
            'notifications',
            'assets',
            'asset_categories',
            'development_fund_payments',
            'tuition_payments',
            'financial_categories',
            'student_attendances',
            'study_classes',
            'teachers',
            'students',
            'users',
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'tpq_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropConstrainedForeignId('tpq_id');
                });
            }
        }
    }
};
