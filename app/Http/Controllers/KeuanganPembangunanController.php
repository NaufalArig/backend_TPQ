<?php

namespace App\Http\Controllers;

use App\Models\KeuanganPembangunan;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class KeuanganPembangunanController extends Controller
{
    public function index()
    {
        return response()->json(
            KeuanganPembangunan::with([
                'financialCategory:id,name',
                'user:id,name,username',
            ])
                ->latest('payment_date')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'financial_category_id' => 'required|exists:financial_categories,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();

        $payment = KeuanganPembangunan::create($validated);

        ActivityLogService::log(
            action: 'create',
            module: 'development_fund_payments',
            entity: $payment,
            oldValues: null,
            newValues: $payment->toArray(),
            description: 'Created development fund payment: Rp ' . number_format($payment->amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Development fund payment created successfully',
            'data' => $payment->load([
                'financialCategory:id,name',
                'user:id,name,username',
            ]),
        ], 201);
    }

    public function show(string $id)
    {
        $payment = KeuanganPembangunan::with([
            'financialCategory:id,name',
            'user:id,name,username',
        ])->findOrFail($id);

        return response()->json($payment);
    }

    public function update(Request $request, string $id)
    {
        $payment = KeuanganPembangunan::findOrFail($id);
        $oldValues = $payment->toArray();

        $validated = $request->validate([
            'financial_category_id' => 'required|exists:financial_categories,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $payment->update($validated);

        ActivityLogService::log(
            action: 'update',
            module: 'development_fund_payments',
            entity: $payment,
            oldValues: $oldValues,
            newValues: $payment->fresh()->toArray(),
            description: 'Updated development fund payment: Rp ' . number_format($payment->amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Development fund payment updated successfully',
            'data' => $payment->load([
                'financialCategory:id,name',
                'user:id,name,username',
            ]),
        ]);
    }

    public function destroy(string $id)
    {
        $payment = KeuanganPembangunan::findOrFail($id);

        $oldValues = $payment->toArray();
        $amount = $payment->amount;

        $payment->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'development_fund_payments',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted development fund payment: Rp ' . number_format($amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Development fund payment deleted successfully',
        ]);
    }
}
