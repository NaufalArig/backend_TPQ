<?php

namespace App\Http\Controllers;

use App\Exports\DataRowsExport;
use App\Models\Asset;
use App\Models\Guru;
use App\Models\KategoriKeuangan;
use App\Models\Kelas;
use App\Models\KeuanganPembangunan;
use App\Models\KeuanganSpp;
use App\Models\Santri;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DataExchangeController extends Controller
{
    private function modules(): array
    {
        return [
            'santri' => [
                'model' => Santri::class,
                'label' => 'santri',
                'unique_by' => 'student_number',
                'columns' => [
                    'id',
                    'study_class_id',
                    'student_number',
                    'tpq_number',
                    'name',
                    'nisn',
                    'nik',
                    'family_card_number',
                    'gender',
                    'birth_place',
                    'birth_date',
                    'join_date',
                    'child_order',
                    'siblings_count',
                    'father_name',
                    'mother_name',
                    'contact_guardian',
                    'hamlet',
                    'village',
                    'district',
                    'city',
                    'province',
                    'formal_school',
                    'formal_class',
                    'npsn',
                    'student_type',
                    'status',
                ],
                'defaults' => [
                    'status' => 'pending',
                    'student_type' => 'regular',
                ],
            ],
            'guru' => [
                'model' => Guru::class,
                'label' => 'guru',
                'unique_by' => 'teacher_number',
                'columns' => [
                    'id',
                    'user_id',
                    'teacher_number',
                    'tpq_number',
                    'name',
                    'gender',
                    'birth_place',
                    'birth_date',
                    'address',
                    'village',
                    'district',
                    'city',
                    'province',
                    'phone',
                    'certificate_from',
                    'certificate_number',
                    'education',
                    'join_date',
                    'leave_date',
                    'status',
                ],
                'defaults' => [
                    'status' => 'pending',
                ],
            ],
            'users' => [
                'model' => User::class,
                'label' => 'users',
                'unique_by' => 'username',
                'columns' => [
                    'id',
                    'name',
                    'username',
                    'email',
                    'password',
                    'role',
                    'status',
                ],
                'defaults' => [
                    'role' => 'teacher',
                    'status' => 'active',
                ],
                'hidden_export' => ['password'],
            ],
            'kelas' => [
                'model' => Kelas::class,
                'label' => 'kelas',
                'unique_by' => 'name',
                'columns' => ['id', 'name', 'description', 'status'],
                'defaults' => ['status' => 'active'],
            ],
            'kategori-keuangan' => [
                'model' => KategoriKeuangan::class,
                'label' => 'kategori-keuangan',
                'unique_by' => 'name',
                'columns' => ['id', 'name', 'description', 'status'],
                'defaults' => ['status' => 'active'],
            ],
            'assets' => [
                'model' => Asset::class,
                'label' => 'aset',
                'unique_by' => 'asset_code',
                'columns' => [
                    'id',
                    'asset_category_id',
                    'asset_code',
                    'name',
                    'brand',
                    'quantity',
                    'unit',
                    'acquisition_date',
                    'source',
                    'location',
                    'condition',
                    'status',
                    'estimated_value',
                    'note',
                ],
                'defaults' => [
                    'quantity' => 1,
                    'unit' => 'unit',
                    'condition' => 'good',
                    'status' => 'available',
                ],
            ],
            'keuangan-spp' => [
                'model' => KeuanganSpp::class,
                'label' => 'keuangan-spp',
                'unique_by' => null,
                'columns' => [
                    'id',
                    'student_id',
                    'user_id',
                    'payment_date',
                    'month',
                    'year',
                    'amount',
                    'note',
                ],
            ],
            'keuangan-pembangunan' => [
                'model' => KeuanganPembangunan::class,
                'label' => 'keuangan-pembangunan',
                'unique_by' => null,
                'columns' => [
                    'id',
                    'financial_category_id',
                    'user_id',
                    'payment_date',
                    'transaction_type',
                    'amount',
                    'note',
                ],
                'defaults' => [
                    'transaction_type' => 'income',
                ],
            ],
        ];
    }

    public function export(Request $request, string $module)
    {
        $config = $this->resolveModule($request, $module);
        $columns = $this->exportColumns($config);
        $modelClass = $config['model'];

        $rows = $modelClass::query()
            ->latest('id')
            ->get()
            ->map(fn (Model $item) => $this->mapModelToRow($item, $columns))
            ->values()
            ->all();

        ActivityLogService::log(
            action: 'export',
            module: $config['label'],
            entity: null,
            oldValues: null,
            newValues: ['format' => 'xlsx', 'rows' => count($rows)],
            description: 'Exported ' . $config['label'] . ' to Excel'
        );

        $fileName = $config['label'] . '-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new DataRowsExport(
                $this->exportHeadings($config, $columns),
                $rows,
                $this->exportTextColumnLetters($config, $columns)
            ),
            $fileName
        );
    }

    public function import(Request $request, string $module)
    {
        $config = $this->resolveModule($request, $module);
        ini_set('memory_limit', '1024M');
        set_time_limit(120);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
        ]);

        try {
            $rawRows = $this->readSpreadsheetRows($request->file('file')->getRealPath());
        } catch (\Throwable $error) {
            return response()->json([
                'message' => str_contains($error->getMessage(), 'ZipArchive')
                    ? 'PHP ZipArchive belum aktif di server yang sedang berjalan. Restart Laragon atau server backend, lalu coba import lagi.'
                    : 'Gagal membaca file Excel: ' . $error->getMessage(),
            ], 422);
        }

        $rows = $this->normalizeRows($rawRows, $config);
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                $result = $this->persistRow($config, $row, $request);

                if ($result === 'updated') {
                    $updated += 1;
                } else {
                    $imported += 1;
                }
            } catch (\Throwable $error) {
                $errors[] = [
                    'row' => $index + 2,
                    'message' => $error->getMessage(),
                ];
            }
        }

        ActivityLogService::log(
            action: 'import',
            module: $config['label'],
            entity: null,
            oldValues: null,
            newValues: [
                'format' => 'xlsx',
                'created' => $imported,
                'updated' => $updated,
                'errors' => count($errors),
            ],
            description: 'Imported ' . $config['label'] . ' from Excel'
        );

        return response()->json([
            'message' => 'Import selesai',
            'created' => $imported,
            'updated' => $updated,
            'errors' => $errors,
        ]);
    }

    private function readSpreadsheetRows(string $path): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }

        if (method_exists($reader, 'setReadFilter')) {
            $reader->setReadFilter(new class implements IReadFilter {
                public function readCell($columnAddress, $row, $worksheetName = ''): bool
                {
                    return $row <= 5000;
                }
            });
        }

        $spreadsheet = $reader->load($path);
        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return $rows;
    }

    private function resolveModule(Request $request, string $module): array
    {
        $modules = $this->modules();

        abort_unless(isset($modules[$module]), 404, 'Module export/import tidak ditemukan');

        $user = $request->user();
        $allowedFinance = ['keuangan-spp', 'keuangan-pembangunan'];
        $allowedTeacher = ['santri'];

        if (
            $user?->role !== 'admin'
            && !($user?->role === 'treasurer' && in_array($module, $allowedFinance, true))
            && !($user?->role === 'teacher' && in_array($module, $allowedTeacher, true))
        ) {
            abort(403, 'Tidak memiliki akses export/import modul ini');
        }

        return $modules[$module];
    }

    private function exportColumns(array $config): array
    {
        $hidden = $config['hidden_export'] ?? [];

        return array_values(array_filter(
            $config['columns'],
            fn (string $column) => !in_array($column, $hidden, true)
        ));
    }

    private function mapModelToRow(Model $item, array $columns): array
    {
        return collect($columns)
            ->map(function (string $column) use ($item) {
                $value = $item->{$column};

                if ($value instanceof \DateTimeInterface) {
                    return $value->format('Y-m-d');
                }

                return $value;
            })
            ->all();
    }

    private function exportHeadings(array $config, array $columns): array
    {
        $labels = $this->headingLabels($config['label']);

        return collect($columns)
            ->map(fn (string $column) => $labels[$column] ?? Str::headline($column))
            ->all();
    }

    private function exportTextColumnLetters(array $config, array $columns): array
    {
        $textColumns = $config['text_export_columns'] ?? [
            'student_number',
            'teacher_number',
            'tpq_number',
            'nisn',
            'nik',
            'family_card_number',
            'phone',
            'certificate_number',
        ];

        return collect($columns)
            ->values()
            ->filter(fn (string $column) => in_array($column, $textColumns, true))
            ->map(function (string $column) use ($columns) {
                $index = array_search($column, $columns, true);

                return Coordinate::stringFromColumnIndex($index + 1);
            })
            ->values()
            ->all();
    }

    private function headingLabels(string $moduleLabel): array
    {
        if ($moduleLabel === 'santri') {
            return [
                'id' => 'ID',
                'study_class_id' => 'ID Kelas',
                'student_number' => 'Induk Santri',
                'tpq_number' => 'No. Induk TPQ',
                'name' => 'Nama Lengkap',
                'nisn' => 'NISN',
                'nik' => 'NIK',
                'family_card_number' => 'No. KK',
                'gender' => 'Jenis Kelamin',
                'birth_place' => 'Tempat Lahir',
                'birth_date' => 'Tanggal Lahir',
                'join_date' => 'Tanggal Masuk',
                'child_order' => 'Anak Ke',
                'siblings_count' => 'Jumlah Saudara',
                'father_name' => 'Nama Ayah',
                'mother_name' => 'Nama Ibu',
                'contact_guardian' => 'Kontak Wali',
                'hamlet' => 'Dusun / Jalan',
                'village' => 'Desa / Kelurahan',
                'district' => 'Kecamatan',
                'city' => 'Kabupaten / Kota',
                'province' => 'Provinsi',
                'formal_school' => 'Nama Sekolah',
                'formal_class' => 'Kelas Formal',
                'npsn' => 'NPSN',
                'student_type' => 'Jenis Santri',
                'status' => 'Status',
            ];
        }

        return [];
    }

    private function normalizeRows(array $sheets, array $config): array
    {
        $sheet = $sheets;

        if (count($sheet) < 2) {
            return [];
        }

        $headerIndex = $this->findHeaderRowIndex($sheet, $config);
        $headings = array_map(
            fn ($heading) => $this->normalizeHeading((string) $heading, $config),
            $sheet[$headerIndex] ?? []
        );

        return collect(array_slice($sheet, $headerIndex + 1))
            ->filter(fn (array $row) => collect($row)->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty())
            ->map(function (array $row) use ($headings) {
                $normalized = [];

                foreach ($headings as $index => $heading) {
                    if ($heading !== '') {
                        $normalized[$heading] = $row[$index] ?? null;
                    }
                }

                return $normalized;
            })
            ->values()
            ->all();
    }

    private function findHeaderRowIndex(array $sheet, array $config): int
    {
        foreach ($sheet as $index => $row) {
            $matchedColumns = collect($row)
                ->map(fn ($heading) => $this->normalizeHeading((string) $heading, $config))
                ->filter(fn ($heading) => in_array($heading, $config['columns'], true))
                ->unique()
                ->count();

            if ($matchedColumns >= 2) {
                return $index;
            }
        }

        return 0;
    }

    private function normalizeHeading(string $heading, array $config): string
    {
        $normalized = Str::of($heading)
            ->lower()
            ->replace(['.', '/', '\\', ':', ';', ',', '-', '_', "\n", "\r", "\t"], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();

        $aliases = $this->headingAliases($config['label']);

        return $aliases[$normalized] ?? Str::snake($normalized);
    }

    private function headingAliases(string $moduleLabel): array
    {
        if ($moduleLabel !== 'santri') {
            return [];
        }

        return [
            'induk santri' => 'student_number',
            'no induk santri' => 'student_number',
            'nomor induk santri' => 'student_number',
            'no induk tpq' => 'tpq_number',
            'induk tpq' => 'tpq_number',
            'nama lengkap' => 'name',
            'nama santri' => 'name',
            'l p' => 'gender',
            'lp' => 'gender',
            'jenis kelamin' => 'gender',
            'anak ke' => 'child_order',
            'dari bersaudara' => 'siblings_count',
            'jumlah saudara' => 'siblings_count',
            'tempat lahir' => 'birth_place',
            'tanggal lahir' => 'birth_date',
            'tgl lahir' => 'birth_date',
            'tgl masuk tpq' => 'join_date',
            'tanggal masuk tpq' => 'join_date',
            'tgl masuk pra ptpt' => 'join_date',
            'tanggal masuk pra ptpt' => 'join_date',
            'nama sekolah' => 'formal_school',
            'npsn' => 'npsn',
            'nisn' => 'nisn',
            'nik' => 'nik',
            'no kk' => 'family_card_number',
            'nomor kk' => 'family_card_number',
            'no kartu keluarga' => 'family_card_number',
            'dusun jl' => 'hamlet',
            'dusun jalan' => 'hamlet',
            'alamat' => 'hamlet',
            'desa' => 'village',
            'kelurahan' => 'village',
            'kecamatan' => 'district',
            'kabupaten' => 'city',
            'kabupaten kota' => 'city',
            'kota' => 'city',
            'provinsi' => 'province',
            'nama ayah' => 'father_name',
            'ayah' => 'father_name',
            'nama ibu' => 'mother_name',
            'ibu' => 'mother_name',
            'status' => 'status',
            'jenis santri' => 'student_type',
        ];
    }

    private function persistRow(array $config, array $row, Request $request): string
    {
        $modelClass = $config['model'];
        $allowedColumns = $config['columns'];
        $data = collect($row)
            ->only($allowedColumns)
            ->map(fn ($value, $column) => $this->normalizeCellValue($column, $value))
            ->all();

        foreach (($config['defaults'] ?? []) as $column => $value) {
            if (!array_key_exists($column, $data) || $data[$column] === null) {
                $data[$column] = $value;
            }
        }

        if (in_array('user_id', $allowedColumns, true) && empty($data['user_id'])) {
            $data['user_id'] = $request->user()?->id;
        }

        $id = $data['id'] ?? null;
        unset($data['id']);

        $model = null;

        if ($id) {
            $model = $modelClass::query()->find($id);
        }

        $uniqueBy = $config['unique_by'] ?? null;

        if (!$model && $uniqueBy && !empty($data[$uniqueBy])) {
            $model = $modelClass::query()->where($uniqueBy, $data[$uniqueBy])->first();
        }

        if ($modelClass === User::class) {
            if (!empty($data['password'])) {
                $data['password'] = Hash::make((string) $data['password']);
            } elseif (!$model) {
                $data['password'] = Hash::make('password');
            } else {
                unset($data['password']);
            }
        }

        if ($modelClass === Santri::class) {
            $data = $this->prepareSantriImportData($data);
        }

        $validator = Validator::make($data, $this->rulesFor($modelClass));

        if ($validator->fails()) {
            throw new \InvalidArgumentException($validator->errors()->first());
        }

        if ($model) {
            $model->update($data);

            return 'updated';
        }

        $modelClass::query()->create($data);

        return 'created';
    }

    private function prepareSantriImportData(array $data): array
    {
        $status = $data['status'] ?? null;

        if (!empty($data['join_date']) && !in_array($status, ['graduated', 'left'], true)) {
            $data['status'] = now()->startOfDay()->greaterThanOrEqualTo(\Carbon\Carbon::parse($data['join_date'])->startOfDay())
                ? 'active'
                : 'pending';

            return $data;
        }

        if (!empty($data['birth_date']) && empty($data['join_date'])) {
            $joinDate = \Carbon\Carbon::parse($data['birth_date'])->copy()->addYears(3);
            $data['join_date'] = $joinDate->format('Y-m-d');

            if (!in_array($status, ['graduated', 'left'], true)) {
                $data['status'] = now()->startOfDay()->greaterThanOrEqualTo($joinDate->startOfDay())
                    ? 'active'
                    : 'pending';
            }
        }

        return $data;
    }

    private function normalizeCellValue(string $column, mixed $value): mixed
    {
        if ($value === '') {
            return null;
        }

        if (in_array($column, ['birth_date', 'join_date', 'leave_date', 'payment_date', 'acquisition_date'], true)) {
            return $this->normalizeDateValue($value);
        }

        if (in_array($column, [
            'student_number',
            'teacher_number',
            'tpq_number',
            'nisn',
            'nik',
            'family_card_number',
            'phone',
            'certificate_number',
        ], true)) {
            return $this->normalizeTextIdentifier($value);
        }

        if ($column === 'gender') {
            $gender = strtolower(trim((string) $value));

            return match ($gender) {
                'l', 'laki laki', 'laki-laki', 'male', 'pria' => 'male',
                'p', 'perempuan', 'female', 'wanita' => 'female',
                default => $value,
            };
        }

        if ($column === 'student_type') {
            $type = strtolower(trim((string) $value));

            return match ($type) {
                'biasa', 'regular' => 'regular',
                'pra ptpt', 'pra qiraati', 'pre qiraati', 'pre_qiraati' => 'pre_qiraati',
                'ptpt', 'qiraati', 'ptpt qiraati' => 'qiraati',
                default => $value,
            };
        }

        if ($column === 'status') {
            $status = strtolower(trim((string) $value));

            return match ($status) {
                'aktif' => 'active',
                'nonaktif', 'non aktif' => 'inactive',
                'lulus' => 'graduated',
                'keluar' => 'left',
                default => $value,
            };
        }

        return $value;
    }

    private function normalizeTextIdentifier(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            return number_format((float) $value, 0, '', '');
        }

        $text = trim((string) $value);

        if (preg_match('/^[0-9]+(\.[0-9]+)?E\+[0-9]+$/i', $text)) {
            return number_format((float) $text, 0, '', '');
        }

        return $text;
    }

    private function normalizeDateValue(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject((float) $value)->format('Y-m-d');
        }

        $date = trim((string) $value);

        foreach (['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y'] as $format) {
            $parsed = \DateTime::createFromFormat($format, $date);

            if ($parsed instanceof \DateTimeInterface) {
                return $parsed->format('Y-m-d');
            }
        }

        return $value;
    }

    private function rulesFor(string $modelClass): array
    {
        return match ($modelClass) {
            Santri::class => [
                'name' => ['required'],
                'gender' => ['nullable', 'in:male,female'],
                'student_type' => ['nullable', 'in:regular,pre_qiraati,qiraati'],
                'status' => ['nullable', 'in:pending,active,graduated,left'],
            ],
            default => [],
        };
    }
}
