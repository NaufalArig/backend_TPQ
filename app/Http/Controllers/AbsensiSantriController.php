<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSantri;
use App\Models\Santri;
use Illuminate\Http\Request;

class AbsensiSantriController extends Controller
{
    public function index(Request $request)
    {
        $attendanceDate = $request->get('attendance_date', now()->format('Y-m-d'));

        $students = Santri::with([
            'studyClass:id,name',
            'attendances' => function ($query) use ($attendanceDate) {
                $query->whereDate('attendance_date', $attendanceDate);
            },
        ])
            ->where('status', 'active')
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
                        'attendance_date' => $attendance->attendance_date->format('Y-m-d'),
                        'status' => $attendance->status,
                        'note' => $attendance->note,
                    ] : null,
                ];
            });

        return response()->json([
            'attendance_date' => $attendanceDate,
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
            AbsensiSantri::updateOrCreate(
                [
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
        $query = AbsensiSantri::with([
            'student:id,study_class_id,name,nisn,student_number',
            'student.studyClass:id,name',
            'user:id,name,username',
        ]);

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
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json(
            $query->latest('attendance_date')->get()
        );
    }
}
