<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseBackupController extends Controller
{
    public function __construct(private DatabaseBackupService $backupService)
    {
    }

    public function index()
    {
        return response()->json([
            'data' => $this->backupService->list(),
        ]);
    }

    public function store()
    {
        $backup = $this->backupService->create('manual');

        ActivityLogService::log(
            action: 'export',
            module: 'database_backups',
            entity: null,
            oldValues: null,
            newValues: $backup,
            description: 'Created manual database backup'
        );

        return response()->json([
            'message' => 'Backup database berhasil dibuat.',
            'data' => $backup,
        ], 201);
    }

    public function download(string $fileName): BinaryFileResponse
    {
        return response()->download($this->backupService->absolutePath($fileName));
    }

    public function destroy(string $fileName)
    {
        $this->backupService->delete($fileName);

        ActivityLogService::log(
            action: 'delete',
            module: 'database_backups',
            entity: null,
            oldValues: ['file' => $fileName],
            newValues: null,
            description: 'Deleted database backup: ' . $fileName
        );

        return response()->json([
            'message' => 'Backup database berhasil dihapus.',
        ]);
    }

    public function restore(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:51200',
        ]);

        $fileName = $request->file('file')->getClientOriginalName();
        $this->backupService->restoreFromUpload($request->file('file'));

        ActivityLogService::log(
            action: 'import',
            module: 'database_backups',
            entity: null,
            oldValues: null,
            newValues: ['file' => $fileName],
            description: 'Restored database from uploaded backup: ' . $fileName
        );

        return response()->json([
            'message' => 'Restore database berhasil.',
        ]);
    }

    public function restoreExisting(string $fileName)
    {
        $this->backupService->restoreFromExisting($fileName);

        ActivityLogService::log(
            action: 'import',
            module: 'database_backups',
            entity: null,
            oldValues: null,
            newValues: ['file' => $fileName],
            description: 'Restored database from existing backup: ' . $fileName
        );

        return response()->json([
            'message' => 'Restore database berhasil.',
        ]);
    }
}
