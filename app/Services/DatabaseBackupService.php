<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

class DatabaseBackupService
{
    private string $disk = 'local';
    private string $directory = 'backups';

    public function list(): array
    {
        $this->ensureDirectoryExists();

        return collect(Storage::disk($this->disk)->files($this->directory))
            ->filter(fn (string $path) => str_ends_with($path, '.sql'))
            ->map(function (string $path) {
                return [
                    'name' => basename($path),
                    'path' => $path,
                    'size' => Storage::disk($this->disk)->size($path),
                    'created_at' => date('Y-m-d H:i:s', Storage::disk($this->disk)->lastModified($path)),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function create(?string $prefix = null): array
    {
        $this->ensureDirectoryExists();

        $database = DB::getDatabaseName();
        $safePrefix = $prefix ?: 'backup';
        $fileName = $safePrefix . '-' . now()->format('Ymd-His') . '.sql';
        $path = $this->directory . '/' . $fileName;

        Storage::disk($this->disk)->put($path, $this->buildSqlDump($database));

        return [
            'name' => $fileName,
            'path' => $path,
            'size' => Storage::disk($this->disk)->size($path),
            'created_at' => date('Y-m-d H:i:s', Storage::disk($this->disk)->lastModified($path)),
        ];
    }

    public function absolutePath(string $fileName): string
    {
        $path = $this->pathFromFileName($fileName);

        if (!Storage::disk($this->disk)->exists($path)) {
            throw new InvalidArgumentException('File backup tidak ditemukan.');
        }

        return Storage::disk($this->disk)->path($path);
    }

    public function delete(string $fileName): void
    {
        $path = $this->pathFromFileName($fileName);

        if (!Storage::disk($this->disk)->exists($path)) {
            throw new InvalidArgumentException('File backup tidak ditemukan.');
        }

        Storage::disk($this->disk)->delete($path);
    }

    public function restoreFromExisting(string $fileName): void
    {
        $this->restoreSql(File::get($this->absolutePath($fileName)));
    }

    public function restoreFromUpload(UploadedFile $file): void
    {
        if ($file->getClientOriginalExtension() !== 'sql') {
            throw new InvalidArgumentException('File restore harus berformat .sql.');
        }

        $content = File::get($file->getRealPath());

        if (!$content) {
            throw new InvalidArgumentException('File restore kosong atau tidak bisa dibaca.');
        }

        $this->restoreSql($content);
    }

    private function buildSqlDump(string $database): string
    {
        $pdo = DB::connection()->getPdo();
        $tables = DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tableKey = 'Tables_in_' . $database;
        $lines = [
            '-- TPQ database backup',
            '-- Generated at ' . now()->toDateTimeString(),
            'SET FOREIGN_KEY_CHECKS=0;',
            'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";',
            '',
        ];

        foreach ($tables as $table) {
            $tableName = $table->{$tableKey} ?? array_values((array) $table)[0] ?? null;

            if (!$tableName) {
                continue;
            }

            $quotedTable = $this->quoteIdentifier($tableName);
            $create = DB::selectOne("SHOW CREATE TABLE {$quotedTable}");
            $createSql = $create->{'Create Table'} ?? null;

            if (!$createSql) {
                continue;
            }

            $lines[] = '';
            $lines[] = '-- Table: ' . $tableName;
            $lines[] = 'DROP TABLE IF EXISTS ' . $quotedTable . ';';
            $lines[] = $createSql . ';';

            DB::table($tableName)->orderBy(DB::raw('1'))->chunk(500, function ($rows) use (&$lines, $pdo, $tableName) {
                foreach ($rows as $row) {
                    $data = (array) $row;
                    $columns = array_map([$this, 'quoteIdentifier'], array_keys($data));
                    $values = array_map(function ($value) use ($pdo) {
                        if ($value === null) {
                            return 'NULL';
                        }

                        if (is_bool($value)) {
                            return $value ? '1' : '0';
                        }

                        return $pdo->quote((string) $value);
                    }, array_values($data));

                    $lines[] = 'INSERT INTO ' . $this->quoteIdentifier($tableName)
                        . ' (' . implode(', ', $columns) . ') VALUES ('
                        . implode(', ', $values) . ');';
                }
            });
        }

        $lines[] = '';
        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        $lines[] = '';

        return implode(PHP_EOL, $lines);
    }

    private function restoreSql(string $sql): void
    {
        $sqlWithoutComments = collect(preg_split('/\r\n|\r|\n/', $sql))
            ->reject(fn (string $line) => str_starts_with(ltrim($line), '--'))
            ->implode(PHP_EOL);

        $statements = $this->splitSqlStatements($sqlWithoutComments);

        if (count($statements) === 0) {
            throw new InvalidArgumentException('File restore tidak berisi query SQL.');
        }

        DB::unprepared('SET FOREIGN_KEY_CHECKS=0;');

        try {
            foreach ($statements as $statement) {
                $trimmed = trim($statement);

                if ($trimmed === '' || str_starts_with($trimmed, '--')) {
                    continue;
                }

                DB::unprepared($trimmed);
            }
        } catch (\Throwable $error) {
            throw new RuntimeException('Restore database gagal: ' . $error->getMessage(), 0, $error);
        } finally {
            DB::unprepared('SET FOREIGN_KEY_CHECKS=1;');
        }
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $current = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $previous = $i > 0 ? $sql[$i - 1] : '';

            if ($char === "'" && !$inDoubleQuote && $previous !== '\\') {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote && $previous !== '\\') {
                $inDoubleQuote = !$inDoubleQuote;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
                $statements[] = $current . ';';
                $current = '';
                continue;
            }

            $current .= $char;
        }

        if (trim($current) !== '') {
            $statements[] = $current;
        }

        return $statements;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function pathFromFileName(string $fileName): string
    {
        $baseName = basename($fileName);

        if ($baseName !== $fileName || !str_ends_with($baseName, '.sql')) {
            throw new InvalidArgumentException('Nama file backup tidak valid.');
        }

        return $this->directory . '/' . $baseName;
    }

    private function ensureDirectoryExists(): void
    {
        if (!Storage::disk($this->disk)->exists($this->directory)) {
            Storage::disk($this->disk)->makeDirectory($this->directory);
        }
    }
}
