<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    public function index()
    {
        return response()->json(
            AssetCategory::withCount('assets')
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:asset_categories,name',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category = AssetCategory::create($validated);

        ActivityLogService::log(
            action: 'create',
            module: 'asset_categories',
            entity: $category,
            oldValues: null,
            newValues: $category->toArray(),
            description: 'Created asset category: ' . $category->name
        );

        return response()->json([
            'message' => 'Asset category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(
            AssetCategory::with('assets')->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $category = AssetCategory::findOrFail($id);
        $oldValues = $category->toArray();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'name')->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category->update($validated);

        ActivityLogService::log(
            action: 'update',
            module: 'asset_categories',
            entity: $category,
            oldValues: $oldValues,
            newValues: $category->fresh()->toArray(),
            description: 'Updated asset category: ' . $category->name
        );

        return response()->json([
            'message' => 'Asset category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroy(string $id)
    {
        $category = AssetCategory::withCount('assets')->findOrFail($id);

        if ($category->assets_count > 0) {
            return response()->json([
                'message' => 'Asset category cannot be deleted because it is still used',
            ], 400);
        }

        $oldValues = $category->toArray();
        $categoryName = $category->name;

        $category->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'asset_categories',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted asset category: ' . $categoryName
        );

        return response()->json([
            'message' => 'Asset category deleted successfully',
        ]);
    }
}
