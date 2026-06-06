<?php

namespace App\Http\Controllers;

use App\Models\Kelas;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KelasController extends Controller
{
    public function index()
    {
        return response()->json(
            Kelas::withCount(['santris' => function ($query) {
                $query->where('status', 'active');
            }])
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:study_classes,name',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $kelas = Kelas::create($validated);

        return response()->json([
            'message' => 'Class created successfully',
            'data' => $kelas,
        ], 201);
    }

    public function show(string $id)
    {
        $kelas = Kelas::with(['santris' => function ($query) {
            $query->where('status', 'active')->orderBy('name');
        }])->findOrFail($id);

        return response()->json($kelas);
    }

    public function update(Request $request, string $id)
    {
        $kelas = Kelas::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('study_classes', 'name')->ignore($kelas->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $kelas->update($validated);

        return response()->json([
            'message' => 'Class updated successfully',
            'data' => $kelas,
        ]);
    }

    public function destroy(string $id)
    {
        $kelas = Kelas::withCount('santris')->findOrFail($id);

        if ($kelas->santris_count > 0) {
            return response()->json([
                'message' => 'Class cannot be deleted because it still has students',
            ], 400);
        }

        $kelas->delete();

        return response()->json([
            'message' => 'Class deleted successfully',
        ]);
    }
}
