<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\AbsensiSantri;
use App\Models\Guru;
use App\Models\Kelas;
use App\Models\Santri;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AbsensiSantriController extends Controller
{
    use UsesTpqScope;

    public function index(Request $request)
    {
        $attendanceDate = $request->get('attendance_date', now()->format('Y-m-d'));
        $studyClassId = $request->get('study_class_id');
        $user = auth()->user();

        $query = Santri::with([
            'studyClass:id,name',
            'attendances' => function ($query) use ($attendanceDate) {
                $query->whereDate('attendance_date', $attendanceDate)
                    ->where('tpq_id', $this->currentTpqId());
            },
        ])
            ->where('tpq_id', $this->currentTpqId())
            ->where('status', 'active');

        if ($studyClassId) {
            $kelas = Kelas::where('id', $studyClassId)
                ->where('tpq_id', $this->currentTpqId())
                ->first();

            if (!$kelas) {
                abort(422, 'Kelas tidak ditemukan pada TPQ ini.');
            }

            if ($user->role === 'teacher') {
                $classIds = $this->getTeacherClassIds();

                if (!$classIds->contains((int) $studyClassId)) {
                    abort(403, 'Anda tidak memiliki akses ke kelas ini.');
                }
            }

            $query->where('study_class_id', $studyClassId);
        }

        if ($user->role === 'teacher') {
            $classIds = $this->getTeacherClassIds();

            if ($classIds->isEmpty()) {
                return response()->json([
                    'attendance_date' => $attendanceDate,
                    'study_class_id' => $studyClassId,
                    'students' => [],
                ]);
            }

            if (!$studyClassId) {
                $query->whereIn('study_class_id', $classIds);
            }
        }

        $students = $query
            ->orderBy('name')
            ->get()
            ->map(function ($student) {
                $attendance = $student->attendances->first();

                return [
                    'id' => $student->id,
                    'study_class_id' => $student->study_class_id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'student_number' => $student->student_number,
                    'study_class' => $student->studyClass,
                    'attendance' => $attendance ? [
                        'id' => $attendance->id,
                        'student_id' => $attendance->student_id,
                        'attendance_date' => Carbon::parse($attendance->attendance_date)->format('Y-m-d'),
                        'status' => $attendance->status,
                        'note' => $attendance->note,
                    ] : null,
                ];
            });

        return response()->json([
            'attendance_date' => $attendanceDate,
            'study_class_id' => $studyClassId,
            'students' => $students,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attendance_date' => 'required|date',
            'attendances' => 'required|array|min:1',
            'attendances.*.student_id' => 'required|exists:students,id',
            'attendances.*.status' => 'required|in:present,permission,sick,absent',
            'attendances.*.note' => 'nullable|string',
        ]);

        foreach ($validated['attendances'] as $item) {
            $this->ensureStudentCanBeAccessed($item['student_id']);

            AbsensiSantri::updateOrCreate(
                [
                    'tpq_id' => $this->currentTpqId(),
                    'student_id' => $item['student_id'],
                    'attendance_date' => $validated['attendance_date'],
                ],
                [
                    'user_id' => auth()->id(),
                    'status' => $item['status'],
                    'note' => $item['note'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Student attendance saved successfully',
        ]);
    }

    public function riwayat(Request $request)
    {
        $user = auth()->user();

        $query = AbsensiSantri::with([
            'student:id,tpq_id,study_class_id,name,nisn,student_number',
            'student.studyClass:id,name',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $this->currentTpqId());

        if ($user->role === 'teacher') {
            $classIds = $this->getTeacherClassIds();

            if ($classIds->isEmpty()) {
                return response()->json([]);
            }

            $query->whereHas('student', function ($q) use ($classIds) {
                $q->where('tpq_id', $this->currentTpqId())
                    ->whereIn('study_class_id', $classIds);
            });
        }

        if ($request->filled('attendance_date')) {
            $query->whereDate('attendance_date', $request->attendance_date);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('attendance_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('attendance_date', '<=', $request->date_to);
        }

        if ($request->filled('student_id')) {
            $this->ensureStudentCanBeAccessed($request->student_id);
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->latest('attendance_date')->get()
        );
    }

    private function getTeacherClassIds()
    {
        $teacher = Guru::where('user_id', auth()->id())
            ->where('tpq_id', $this->currentTpqId())
            ->first();

        if (!$teacher) {
            return collect();
        }

        return Kelas::where('teacher_id', $teacher->id)
            ->where('tpq_id', $this->currentTpqId())
            ->pluck('id');
    }

    private function ensureStudentCanBeAccessed($studentId): void
    {
        $user = auth()->user();

        $student = Santri::where('id', $studentId)
            ->where('tpq_id', $this->currentTpqId())
            ->first();

        if (!$student) {
            abort(422, 'Santri tidak ditemukan pada TPQ ini.');
        }

        if ($user->role === 'teacher') {
            $classIds = $this->getTeacherClassIds();

            if (!$student->study_class_id || !$classIds->contains($student->study_class_id)) {
                abort(403, 'Anda tidak memiliki akses ke santri ini.');
            }
        }
    }
}
