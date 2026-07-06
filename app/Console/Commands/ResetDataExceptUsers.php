<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDataExceptUsers extends Command
{
    protected $signature = 'tpq:reset-data {--force : Run without confirmation}';

    protected $description = 'Reset application data without deleting users and TPQ records';

    public function handle(): int
    {
        $tables = [
            'personal_access_tokens',
            'password_reset_tokens',
            'failed_jobs',
            'activity_logs',
            'notifications',
            'student_attendances',
            'tuition_payments',
            'development_fund_payments',
            'assets',
            'students',
            'study_classes',
            'teachers',
            'financial_categories',
            'asset_categories',
        ];

        $this->warn('Command ini akan menghapus semua data aplikasi kecuali tabel users dan tpqs.');
        $this->line('Akun login tetap ada, tetapi token login aktif akan dihapus.');

        if (!$this->option('force') && !$this->confirm('Lanjut reset data?', false)) {
            $this->info('Reset dibatalkan.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        try {
            foreach ($tables as $table) {
                if (!Schema::hasTable($table)) {
                    $this->line("Skip {$table}: tabel tidak ditemukan.");
                    continue;
                }

                DB::table($table)->truncate();
                $this->info("Reset tabel: {$table}");
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->info('Reset selesai. Data users dan tpqs tidak dihapus.');

        return self::SUCCESS;
    }
}
