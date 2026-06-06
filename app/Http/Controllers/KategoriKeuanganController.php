<?php

namespace App\Http\Controllers;

use App\Models\KategoriKeuangan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriKeuanganController extends Controller
{
    public function index()
    {
        return response()->json(
            KategoriKeuangan::latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:financial_categories,name',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category = KategoriKeuangan::create($validated);

        return response()->json([
            'message' => 'Financial category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(
            KategoriKeuangan::findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $category = KategoriKeuangan::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_categories', 'name')->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Financial category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroy(string $id)
    {
        $category = KategoriKeuangan::findOrFail($id);

        if ($category->pembangunan()->count() > 0) {
            return response()->json([
                'message' => 'Financial category cannot be deleted because it is still used',
            ], 400);
        }

        $category->delete();

        return response()->json([
            'message' => 'Financial category deleted successfully',
        ]);
    }
}
