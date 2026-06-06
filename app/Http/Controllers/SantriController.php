<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Services\ActivityLogService;

class SantriController extends Controller
{
    public function index()
    {
        return response()->json(
            Santri::with('studyClass')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'study_class_id' => 'nullable|exists:study_classes,id',

            'student_number' => 'nullable|string|max:255',
            'tpq_number' => 'nullable|string|max:255',

            'name' => 'required|string|max:255',

            'nisn' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('students', 'nisn'),
            ],

            'nik' => 'nullable|string|max:255',
            'family_card_number' => 'nullable|string|max:255',

            'gender' => 'required|in:male,female',

            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'required|date',

            'child_order' => 'nullable|integer|min:1',
            'siblings_count' => 'nullable|integer|min:0',

            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',

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

        $statusData = $this->calculateJoinDateAndStatus(
            $validated['birth_date'],
            $validated['status'] ?? null
        );

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
        $student = Santri::with('studyClass')->findOrFail($id);

        return response()->json($student);
    }

    public function update(Request $request, string $id)
    {
        $student = Santri::findOrFail($id);
        $oldValues = $student->toArray();

        $validated = $request->validate([
            'study_class_id' => 'nullable|exists:study_classes,id',

            'student_number' => 'nullable|string|max:255',
            'tpq_number' => 'nullable|string|max:255',

            'name' => 'required|string|max:255',

            'nisn' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('students', 'nisn')->ignore($student->id),
            ],

            'nik' => 'nullable|string|max:255',
            'family_card_number' => 'nullable|string|max:255',

            'gender' => 'required|in:male,female',

            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'required|date',

            'child_order' => 'nullable|integer|min:1',
            'siblings_count' => 'nullable|integer|min:0',

            'father_name' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',

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

        $statusData = $this->calculateJoinDateAndStatus(
            $validated['birth_date'],
            $validated['status'] ?? $student->status
        );

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

        $student->update($validated);

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
        $student = Santri::findOrFail($id);

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

    private function calculateJoinDateAndStatus(string $birthDate, ?string $currentStatus = null): array
    {
        $joinDate = Carbon::parse($birthDate)->copy()->addYears(3);

        if (in_array($currentStatus, ['graduated', 'left'])) {
            $status = $currentStatus;
        } else {
            $status = now()->greaterThanOrEqualTo($joinDate)
                ? 'active'
                : 'pending';
        }

        return [
            'join_date' => $joinDate->format('Y-m-d'),
            'status' => $status,
        ];
    }
}
