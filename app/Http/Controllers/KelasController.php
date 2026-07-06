<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\Guru;
use App\Models\Kelas;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Santri;

class KelasController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        $user = auth()->user();

        $query = Kelas::with([
            'teacher:id,name',
            'santris' => function ($query) {
                $query->select('id', 'tpq_id', 'study_class_id', 'name', 'status', 'join_date')
                    ->where('status', 'active')
                    ->orderBy('name');
            },
        ])
            ->withCount(['santris' => function ($query) {
                $query->where('status', 'active');
            }])
            ->where('tpq_id', $this->currentTpqId());

        if ($user->role === 'teacher') {
            $teacher = Guru::where('user_id', $user->id)
                ->where('tpq_id', $this->currentTpqId())
                ->first();

            if (!$teacher) {
                return response()->json([]);
            }

            $query->where('teacher_id', $teacher->id);
        }

        return response()->json(
            $query->latest()->get()
        );
    }

    public function store(Request $request)
    {
        if (auth()->user()->role === 'teacher') {
            abort(403, 'Guru tidak memiliki akses untuk mengelola data kelas.');
        }

        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:teachers,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('study_classes', 'name')
                    ->where('tpq_id', $this->currentTpqId()),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $this->ensureTeacherBelongsToCurrentTpq($validated['teacher_id'] ?? null);

        $validated['tpq_id'] = $this->currentTpqId();

        $kelas = Kelas::create($validated);

        return response()->json([
            'message' => 'Class created successfully',
            'data' => $kelas->load('teacher:id,name'),
        ], 201);
    }

    public function show(string $id)
    {
        $user = auth()->user();

        $query = Kelas::with([
            'teacher:id,name',
            'santris' => function ($query) {
                $query->where('status', 'active')->orderBy('name');
            },
        ])
            ->where('tpq_id', $this->currentTpqId());

        if ($user->role === 'teacher') {
            $teacher = Guru::where('user_id', $user->id)
                ->where('tpq_id', $this->currentTpqId())
                ->first();

            if (!$teacher) {
                abort(403, 'Akun guru belum terhubung dengan data guru.');
            }

            $query->where('teacher_id', $teacher->id);
        }

        $kelas = $query->findOrFail($id);

        return response()->json($kelas);
    }

    public function update(Request $request, string $id)
    {
        if (auth()->user()->role === 'teacher') {
            abort(403, 'Guru tidak memiliki akses untuk mengelola data kelas.');
        }

        $kelas = Kelas::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $validated = $request->validate([
            'teacher_id' => 'nullable|exists:teachers,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('study_classes', 'name')
                    ->where('tpq_id', $this->currentTpqId())
                    ->ignore($kelas->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $this->ensureTeacherBelongsToCurrentTpq($validated['teacher_id'] ?? null);

        unset($validated['tpq_id']);

        $kelas->update($validated);

        return response()->json([
            'message' => 'Class updated successfully',
            'data' => $kelas->fresh('teacher:id,name'),
        ]);
    }

    public function destroy(string $id)
    {
        if (auth()->user()->role === 'teacher') {
            abort(403, 'Guru tidak memiliki akses untuk mengelola data kelas.');
        }

        $kelas = Kelas::withCount('santris')
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        if ($kelas->santris_count > 0) {
            return response()->json([
                'message' => 'Class cannot be deleted because it still has students',
            ], 400);
        }

        $oldValues = $kelas->toArray();

        $kelas->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'classes',
            entity: $kelas,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted class: ' . ($oldValues['name'] ?? $kelas->id)
        );

        return response()->json([
            'message' => 'Class deleted successfully',
        ]);
    }

    public function availableSantri(string $id)
    {
        $kelas = $this->findAccessibleClass($id);

        $santris = Santri::where('tpq_id', $this->currentTpqId())
            ->where('status', 'active')
            ->whereNull('study_class_id')
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'nisn',
                'birth_date',
                'join_date',
                'status',
                'study_class_id',
            ]);

        return response()->json([
            'class' => $kelas,
            'data' => $santris,
        ]);
    }

    public function assignSantri(Request $request, string $id)
    {
        $kelas = $this->findAccessibleClass($id);

        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
        ]);

        $students = Santri::where('tpq_id', $this->currentTpqId())
            ->where('status', 'active')
            ->whereIn('id', $validated['student_ids'])
            ->get();

        if ($students->count() !== count($validated['student_ids'])) {
            return response()->json([
                'message' => 'Sebagian santri tidak ditemukan atau bukan santri aktif.',
            ], 422);
        }

        $alreadyHasClass = $students
            ->whereNotNull('study_class_id')
            ->values();

        if ($alreadyHasClass->count() > 0) {
            return response()->json([
                'message' => 'Ada santri yang sudah masuk kelas lain.',
                'students' => $alreadyHasClass->map(function ($student) {
                    return [
                        'id' => $student->id,
                        'name' => $student->name,
                        'study_class_id' => $student->study_class_id,
                    ];
                }),
            ], 422);
        }

        Santri::where('tpq_id', $this->currentTpqId())
            ->whereIn('id', $validated['student_ids'])
            ->whereNull('study_class_id')
            ->update([
                'study_class_id' => $kelas->id,
            ]);

        return response()->json([
            'message' => 'Santri berhasil dimasukkan ke kelas.',
        ]);
    }

    private function findAccessibleClass(string $id): Kelas
    {
        $user = auth()->user();

        $query = Kelas::where('tpq_id', $this->currentTpqId());

        if ($user->role === 'teacher') {
            $teacher = Guru::where('user_id', $user->id)
                ->where('tpq_id', $this->currentTpqId())
                ->first();

            if (!$teacher) {
                abort(403, 'Akun guru belum terhubung dengan data guru.');
            }

            $query->where('teacher_id', $teacher->id);
        }

        return $query->findOrFail($id);
    }

    private function ensureTeacherBelongsToCurrentTpq($teacherId): void
    {
        if (!$teacherId) {
            return;
        }

        $exists = Guru::where('id', $teacherId)
            ->where('tpq_id', $this->currentTpqId())
            ->exists();

        if (!$exists) {
            abort(422, 'Guru tidak ditemukan pada TPQ ini.');
        }
    }
}
