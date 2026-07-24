<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Notification;
use App\Models\Santri;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogService;

class SantriController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        $user = auth()->user();

        $query = Santri::with('studyClass')
            ->where('tpq_id', $this->currentTpqId());

        if ($user->role === 'teacher') {
            $teacher = Guru::where('user_id', $user->id)
                ->where('tpq_id', $this->currentTpqId())
                ->first();

            if (!$teacher) {
                return response()->json([]);
            }

            $classIds = Kelas::where('teacher_id', $teacher->id)
                ->where('tpq_id', $this->currentTpqId())
                ->pluck('id');

            $query->whereIn('study_class_id', $classIds);
        }

        return response()->json(
            $query->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'study_class_id' => 'nullable|exists:study_classes,id',

            'student_number' => 'nullable|string|max:255',
            'tpq_number' => 'nullable|string|max:255',

            'name'               => ['required', 'string', 'max:255', "regex:/^[\p{L}\s.'-]+$/u"],

            'nisn' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('students', 'nisn'),
            ],

            'nik'                => ['nullable', 'digits:16'],
            'family_card_number' => ['nullable', 'digits:16'],

            'gender' => 'required|in:male,female',

            'birth_place'        => ['nullable', 'string', 'max:255', "regex:/^[\p{L}\s.,'-]+$/u"],
            'birth_date' => 'required|date',

            'child_order' => 'nullable|integer|min:1',
            'siblings_count' => 'nullable|integer|min:0',

            'father_name'        => ['nullable', 'string', 'max:255', "regex:/^[\p{L}\s.'-]+$/u"],
            'mother_name'        => ['nullable', 'string', 'max:255', "regex:/^[\p{L}\s.'-]+$/u"],
            'contact_guardian'   => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],

            'hamlet' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',

            'formal_school' => 'nullable|string|max:255',
            'formal_class' => 'nullable|string|max:255',
            'npsn' => 'nullable|string|max:255',

            'student_type' => 'required|in:regular,pre_qiraati,qiraati',

            'status' => 'nullable|in:pending,active,graduated,left',

            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'family_card_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'birth_certificate_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);

        $this->ensureClassBelongsToCurrentTpq($validated['study_class_id'] ?? null);
        $this->ensureTeacherCanAccessClass($validated['study_class_id'] ?? null);

        $statusData = $this->calculateJoinDateAndStatus(
            $validated['birth_date'],
            $validated['status'] ?? null
        );

        $validated['tpq_id'] = $this->currentTpqId();
        $validated['join_date'] = $statusData['join_date'];
        $validated['status'] = $statusData['status'];

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')
                ->store('students/photos', 'public');
        }

        if ($request->hasFile('family_card_file')) {
            $validated['family_card_file'] = $request->file('family_card_file')
                ->store('students/family-cards', 'public');
        }

        if ($request->hasFile('birth_certificate_file')) {
            $validated['birth_certificate_file'] = $request->file('birth_certificate_file')
                ->store('students/birth-certificates', 'public');
        }

        $student = Santri::create($validated);

        $this->createAgeNotificationIfNeeded($student);

        ActivityLogService::log(
            action: 'create',
            module: 'students',
            entity: $student,
            oldValues: null,
            newValues: $student->toArray(),
            description: 'Created student: ' . $student->name
        );

        return response()->json([
            'message' => 'Student created successfully',
            'data' => $student->load('studyClass'),
        ], 201);
    }

    public function show(string $id)
    {
        $student = $this->findStudentByTpqAndRole($id);

        return response()->json(
            $student->load('studyClass')
        );
    }

    public function update(Request $request, string $id)
    {
        $student = $this->findStudentByTpqAndRole($id);
        $oldValues = $student->toArray();

        $validated = $request->validate([
            'study_class_id' => 'nullable|exists:study_classes,id',

            'student_number' => 'nullable|string|max:255',
            'tpq_number' => 'nullable|string|max:255',

            'name'               => ['required', 'string', 'max:255', "regex:/^[\p{L}\s.'-]+$/u"],

            'nisn' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('students', 'nisn')->ignore($student->id),
            ],

            'nik'                => ['nullable', 'digits:16'],
            'family_card_number' => ['nullable', 'digits:16'],

            'gender' => 'required|in:male,female',

            'birth_place'        => ['nullable', 'string', 'max:255', "regex:/^[\p{L}\s.,'-]+$/u"],
            'birth_date' => 'required|date',

            'child_order' => 'nullable|integer|min:1',
            'siblings_count' => 'nullable|integer|min:0',

            'father_name'        => ['nullable', 'string', 'max:255', "regex:/^[\p{L}\s.'-]+$/u"],
            'mother_name'        => ['nullable', 'string', 'max:255', "regex:/^[\p{L}\s.'-]+$/u"],
            'contact_guardian'   => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],

            'hamlet' => 'nullable|string|max:255',
            'village' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',

            'formal_school' => 'nullable|string|max:255',
            'formal_class' => 'nullable|string|max:255',
            'npsn' => 'nullable|string|max:255',

            'student_type' => 'required|in:regular,pre_qiraati,qiraati',

            'status' => 'nullable|in:pending,active,graduated,left',

            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'family_card_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            'birth_certificate_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
        ]);

        $this->ensureClassBelongsToCurrentTpq($validated['study_class_id'] ?? null);
        $this->ensureTeacherCanAccessClass($validated['study_class_id'] ?? null);

        $statusData = $this->calculateJoinDateAndStatus(
            $validated['birth_date'],
            $validated['status'] ?? $student->status
        );

        unset($validated['tpq_id']);

        $validated['join_date'] = $statusData['join_date'];
        $validated['status'] = $statusData['status'];

        if ($request->hasFile('photo')) {
            if ($student->photo) {
                Storage::disk('public')->delete($student->photo);
            }

            $validated['photo'] = $request->file('photo')
                ->store('students/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        if ($request->hasFile('family_card_file')) {
            if ($student->family_card_file) {
                Storage::disk('public')->delete($student->family_card_file);
            }

            $validated['family_card_file'] = $request->file('family_card_file')
                ->store('students/family-cards', 'public');
        } else {
            unset($validated['family_card_file']);
        }

        if ($request->hasFile('birth_certificate_file')) {
            if ($student->birth_certificate_file) {
                Storage::disk('public')->delete($student->birth_certificate_file);
            }

            $validated['birth_certificate_file'] = $request->file('birth_certificate_file')
                ->store('students/birth-certificates', 'public');
        } else {
            unset($validated['birth_certificate_file']);
        }


        if ($student->birth_date !== $validated['birth_date']) {
            $validated['age_notification_sent'] = false;
        }

        $student->update($validated);

        $this->createAgeNotificationIfNeeded($student->fresh());

        ActivityLogService::log(
            action: 'update',
            module: 'students',
            entity: $student,
            oldValues: $oldValues,
            newValues: $student->fresh()->toArray(),
            description: 'Updated student: ' . $student->name
        );

        return response()->json([
            'message' => 'Student updated successfully',
            'data' => $student->fresh('studyClass'),
        ]);
    }

    public function destroy(string $id)
    {
        $student = $this->findStudentByTpqAndRole($id);

        $oldValues = $student->toArray();
        $studentName = $student->name;

        if ($student->photo) {
            Storage::disk('public')->delete($student->photo);
        }

        if ($student->family_card_file) {
            Storage::disk('public')->delete($student->family_card_file);
        }

        if ($student->birth_certificate_file) {
            Storage::disk('public')->delete($student->birth_certificate_file);
        }

        $student->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'students',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted student: ' . $studentName
        );

        return response()->json([
            'message' => 'Student deleted successfully',
        ]);
    }

    public function activate(string $id)
    {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Hanya admin yang dapat mengaktifkan santri.');
        }

        $student = Santri::where('tpq_id', $this->currentTpqId())
            ->where('status', 'pending')
            ->findOrFail($id);

        if (!$student->join_date || now()->startOfDay()->lt(Carbon::parse($student->join_date)->startOfDay())) {
            return response()->json([
                'message' => 'Santri belum mencapai usia 3 tahun.',
            ], 422);
        }

        $oldValues = $student->toArray();

        $student->update([
            'status' => 'active',
        ]);

        ActivityLogService::log(
            action: 'update',
            module: 'students',
            entity: $student,
            oldValues: $oldValues,
            newValues: $student->fresh()->toArray(),
            description: 'Activated student manually: ' . $student->name
        );

        return response()->json([
            'message' => 'Santri berhasil diaktifkan.',
            'data' => $student->fresh('studyClass'),
        ]);
    }

    private function findStudentByTpqAndRole(string $id): Santri
    {
        $user = auth()->user();

        $query = Santri::where('tpq_id', $this->currentTpqId());

        if ($user->role === 'teacher') {
            $teacher = Guru::where('user_id', $user->id)
                ->where('tpq_id', $this->currentTpqId())
                ->first();

            if (!$teacher) {
                abort(403, 'Akun guru belum terhubung dengan data guru.');
            }

            $classIds = Kelas::where('teacher_id', $teacher->id)
                ->where('tpq_id', $this->currentTpqId())
                ->pluck('id');

            $query->whereIn('study_class_id', $classIds);
        }

        return $query->findOrFail($id);
    }

    private function ensureClassBelongsToCurrentTpq($studyClassId): void
    {
        if (!$studyClassId) {
            return;
        }

        $exists = Kelas::where('id', $studyClassId)
            ->where('tpq_id', $this->currentTpqId())
            ->exists();

        if (!$exists) {
            abort(422, 'Kelas tidak ditemukan pada TPQ ini.');
        }
    }

    private function ensureTeacherCanAccessClass($studyClassId): void
    {
        $user = auth()->user();

        if ($user->role !== 'teacher' || !$studyClassId) {
            return;
        }

        $teacher = Guru::where('user_id', $user->id)
            ->where('tpq_id', $this->currentTpqId())
            ->first();

        if (!$teacher) {
            abort(403, 'Akun guru belum terhubung dengan data guru.');
        }

        $allowed = Kelas::where('id', $studyClassId)
            ->where('tpq_id', $this->currentTpqId())
            ->where('teacher_id', $teacher->id)
            ->exists();

        if (!$allowed) {
            abort(403, 'Anda tidak memiliki akses ke kelas ini.');
        }
    }

    private function calculateJoinDateAndStatus(string $birthDate, ?string $currentStatus = null): array
    {
        $joinDate = Carbon::parse($birthDate)->copy()->addYears(3);

        if (in_array($currentStatus, ['graduated', 'left'])) {
            $status = $currentStatus;
        } else {
            $status = $currentStatus === 'active' ? 'active' : 'pending';
        }

        return [
            'join_date' => $joinDate->format('Y-m-d'),
            'status' => $status,
        ];
    }

    private function createAgeNotificationIfNeeded(Santri $student): void
    {
        if (
            $student->status !== 'pending' ||
            !$student->tpq_id ||
            !$student->join_date
        ) {
            return;
        }

        $joinDate = Carbon::parse($student->join_date)->startOfDay();
        $daysLeft = now()->startOfDay()->diffInDays($joinDate, false);

        if ($daysLeft > 7) {
            return;
        }

        $type = $daysLeft > 0 ? 'student_age_warning' : 'student_age_due';

        $exists = Notification::where('tpq_id', $student->tpq_id)
            ->where('student_id', $student->id)
            ->where('type', $type)
            ->exists();

        if ($exists) {
            return;
        }

        if ($daysLeft > 0) {
            $title = 'Santri Hampir Siap Diaktifkan';
            $message = $student->name . ' akan mencapai usia 3 tahun pada ' .
                $joinDate->format('d-m-Y') . '. Siapkan proses aktivasi santri.';
        } else {
            $title = 'Santri Perlu Diaktifkan';
            $message = $student->name . ' sudah mencapai usia 3 tahun. Segera aktifkan santri dan hubungi wali santri.';
        }

        Notification::create([
            'tpq_id' => $student->tpq_id,
            'student_id' => $student->id,
            'user_id' => auth()->id(),
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    public function graduate(string $id)
    {
        $student = $this->findStudentByTpqAndRole($id);

        if ($student->status !== 'active') {
            return response()->json([
                'message' => 'Hanya santri berstatus aktif yang dapat diluluskan (naik tingkat).',
            ], 422);
        }

        $oldValues = $student->toArray();

        $student->update([
            'status'         => 'active',
            'study_class_id' => null,
        ]);

        ActivityLogService::log(
            action: 'update',
            module: 'students',
            entity: $student,
            oldValues: $oldValues,
            newValues: $student->fresh()->toArray(),
            description: 'Naik tingkat (lepas kelas): ' . $student->name
        );

        return response()->json([
            'message' => 'Santri berhasil diluluskan (naik tingkat) & siap dimasukkan ke kelas berikutnya.',
            'data'    => $student->fresh('studyClass'),
        ]);
    }
}
