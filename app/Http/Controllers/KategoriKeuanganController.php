<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\KategoriKeuangan;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KategoriKeuanganController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        return response()->json(
            KategoriKeuangan::where('tpq_id', $this->currentTpqId())
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
                Rule::unique('financial_categories', 'name')
                    ->where(fn ($query) => $query->where('tpq_id', $this->currentTpqId())),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['tpq_id'] = $this->currentTpqId();

        $category = KategoriKeuangan::create($validated);

        return response()->json([
            'message' => 'Financial category created successfully',
            'data' => $category,
        ], 201);
    }

    public function show(string $id)
    {
        $category = KategoriKeuangan::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = KategoriKeuangan::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('financial_categories', 'name')
                    ->where(fn ($query) => $query->where('tpq_id', $this->currentTpqId()))
                    ->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        unset($validated['tpq_id']);

        $category->update($validated);

        return response()->json([
            'message' => 'Financial category updated successfully',
            'data' => $category->fresh(),
        ]);
    }

    public function destroy(string $id)
    {
        $category = KategoriKeuangan::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        if ($category->pembangunan()->count() > 0) {
            return response()->json([
                'message' => 'Financial category cannot be deleted because it is still used',
            ], 400);
        }

        $oldValues = $category->toArray();

        $category->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'financial_categories',
            entity: $category,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted financial category: ' . ($oldValues['name'] ?? $category->id)
        );

        return response()->json([
            'message' => 'Financial category deleted successfully',
        ]);
    }
}
