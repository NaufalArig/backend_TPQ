<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
use App\Models\KategoriKeuangan;
use App\Models\KeuanganPembangunan;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class KeuanganPembangunanController extends Controller
{
    use UsesTpqScope;

    public function index()
    {
        return response()->json(
            KeuanganPembangunan::with([
                'financialCategory:id,tpq_id,name',
                'user:id,tpq_id,name,username',
            ])
                ->where('tpq_id', $this->currentTpqId())
                ->latest('payment_date')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'financial_category_id' => 'required|exists:financial_categories,id',
            'payment_date' => 'required|date',
            'transaction_type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $this->ensureFinancialCategoryBelongsToCurrentTpq($validated['financial_category_id']);

        $validated['tpq_id'] = $this->currentTpqId();
        $validated['user_id'] = auth()->id();

        $payment = KeuanganPembangunan::create($validated);

        ActivityLogService::log(
            action: 'create',
            module: 'development_fund_payments',
            entity: $payment,
            oldValues: null,
            newValues: $payment->toArray(),
            description: 'Created development fund ' . $payment->transaction_type . ': Rp ' . number_format($payment->amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Development fund payment created successfully',
            'data' => $payment->load([
                'financialCategory:id,tpq_id,name',
                'user:id,tpq_id,name,username',
            ]),
        ], 201);
    }

    public function show(string $id)
    {
        $payment = KeuanganPembangunan::with([
            'financialCategory:id,tpq_id,name',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        return response()->json($payment);
    }

    public function update(Request $request, string $id)
    {
        $payment = KeuanganPembangunan::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

        $oldValues = $payment->toArray();

        $validated = $request->validate([
            'financial_category_id' => 'required|exists:financial_categories,id',
            'payment_date' => 'required|date',
            'transaction_type' => 'required|in:income,expense',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $this->ensureFinancialCategoryBelongsToCurrentTpq($validated['financial_category_id']);

        unset($validated['tpq_id'], $validated['user_id']);

        $payment->update($validated);

        ActivityLogService::log(
            action: 'update',
            module: 'development_fund_payments',
            entity: $payment,
            oldValues: $oldValues,
            newValues: $payment->fresh()->toArray(),
            description: 'Updated development fund ' . $payment->transaction_type . ': Rp ' . number_format($payment->amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Development fund payment updated successfully',
            'data' => $payment->fresh([
                'financialCategory:id,tpq_id,name',
                'user:id,tpq_id,name,username',
            ]),
        ]);
    }

    public function destroy(string $id)
    {
        $payment = KeuanganPembangunan::where('tpq_id', $this->currentTpqId())
            ->findOrFail($id);

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

    private function ensureFinancialCategoryBelongsToCurrentTpq($categoryId): void
    {
        $exists = KategoriKeuangan::where('id', $categoryId)
            ->where('tpq_id', $this->currentTpqId())
            ->exists();

        if (!$exists) {
            abort(422, 'Kategori keuangan tidak ditemukan pada TPQ ini.');
        }
    }
}
