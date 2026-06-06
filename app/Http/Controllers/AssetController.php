<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $query = Asset::with('category:id,name,status');

        if ($request->filled('asset_category_id')) {
            $query->where('asset_category_id', $request->asset_category_id);
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->condition);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('asset_code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->latest()->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'asset_code' => 'nullable|string|max:255|unique:assets,asset_code',
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'acquisition_date' => 'nullable|date',
            'source' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'condition' => 'required|in:good,minor_damage,damaged,lost',
            'status' => 'required|in:available,in_use,maintenance,disposed',
            'estimated_value' => 'nullable|numeric|min:0',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'note' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')
                ->store('assets/photos', 'public');
        }

        $asset = Asset::create($validated);

        ActivityLogService::log(
            action: 'create',
            module: 'assets',
            entity: $asset,
            oldValues: null,
            newValues: $asset->toArray(),
            description: 'Created asset: ' . $asset->name
        );

        return response()->json([
            'message' => 'Asset created successfully',
            'data' => $asset->load('category:id,name,status'),
        ], 201);
    }

    public function show(string $id)
    {
        return response()->json(
            Asset::with('category:id,name,status')->findOrFail($id)
        );
    }

    public function update(Request $request, string $id)
    {
        $asset = Asset::findOrFail($id);
        $oldValues = $asset->toArray();

        $validated = $request->validate([
            'asset_category_id' => 'nullable|exists:asset_categories,id',
            'asset_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('assets', 'asset_code')->ignore($asset->id),
            ],
            'name' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'quantity' => 'required|integer|min:1',
            'unit' => 'nullable|string|max:50',
            'acquisition_date' => 'nullable|date',
            'source' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'condition' => 'required|in:good,minor_damage,damaged,lost',
            'status' => 'required|in:available,in_use,maintenance,disposed',
            'estimated_value' => 'nullable|numeric|min:0',
            'photo' => 'nullable|file|mimes:jpg,jpeg,png,webp|max:2048',
            'note' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            if ($asset->photo) {
                Storage::disk('public')->delete($asset->photo);
            }

            $validated['photo'] = $request->file('photo')
                ->store('assets/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        $asset->update($validated);

        ActivityLogService::log(
            action: 'update',
            module: 'assets',
            entity: $asset,
            oldValues: $oldValues,
            newValues: $asset->fresh()->toArray(),
            description: 'Updated asset: ' . $asset->name
        );

        return response()->json([
            'message' => 'Asset updated successfully',
            'data' => $asset->fresh('category:id,name,status'),
        ]);
    }

    public function destroy(string $id)
    {
        $asset = Asset::findOrFail($id);

        $oldValues = $asset->toArray();
        $assetName = $asset->name;

        if ($asset->photo) {
            Storage::disk('public')->delete($asset->photo);
        }

        $asset->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'assets',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted asset: ' . $assetName
        );

        return response()->json([
            'message' => 'Asset deleted successfully',
        ]);
    }
}
