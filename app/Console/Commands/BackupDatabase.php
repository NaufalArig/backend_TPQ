<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'backup:database';

    protected $description = 'Create a SQL database backup file';

    public function handle(DatabaseBackupService $backupService): int
    {
        $backup = $backupService->create('auto');

        $this->info('Database backup created: ' . $backup['name']);

        return self::SUCCESS;
    }
}
