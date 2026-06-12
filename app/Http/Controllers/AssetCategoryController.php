<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\AssetCategory;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AssetCategoryController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        return response()->json(
            AssetCategory::withCount('assets')
                ->where('tpq_id', $this->currentTpqId())
                ->latest()
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'name')
                    ->where(fn ($query) => $query->where('tpq_id', $this->currentTpqId())),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['tpq_id'] = $this->currentTpqId();

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
        $category = AssetCategory::with('assets')
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = AssetCategory::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $oldValues = $category->toArray();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('asset_categories', 'name')
                    ->where(fn ($query) => $query->where('tpq_id', $this->currentTpqId()))
                    ->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        unset($validated['tpq_id']);

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
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $category = AssetCategory::withCount('assets')
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

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
