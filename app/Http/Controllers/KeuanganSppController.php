<?php

namespace App\Http\Controllers;

use App\Models\KeuanganSpp;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;

class KeuanganSppController extends Controller
{
    public function index()
    {
        return response()->json(
            KeuanganSpp::with([
                'student:id,name,nisn',
                'user:id,name,username',
            ])
                ->latest('payment_date')
                ->get()
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'payment_date' => 'required|date',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $validated['user_id'] = auth()->id();

        $payment = KeuanganSpp::create($validated);

        ActivityLogService::log(
            action: 'create',
            module: 'tuition_payments',
            entity: $payment,
            oldValues: null,
            newValues: $payment->toArray(),
            description: 'Created tuition payment: Rp ' . number_format($payment->amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Tuition payment created successfully',
            'data' => $payment->load([
                'student:id,name,nisn',
                'user:id,name,username',
            ]),
        ], 201);
    }

    public function show(string $id)
    {
        $payment = KeuanganSpp::with([
            'student:id,name,nisn',
            'user:id,name,username',
        ])->findOrFail($id);

        return response()->json($payment);
    }

    public function update(Request $request, string $id)
    {
        $payment = KeuanganSpp::findOrFail($id);
        $oldValues = $payment->toArray();

        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'payment_date' => 'required|date',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2000|max:2100',
            'amount' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        $payment->update($validated);

        ActivityLogService::log(
            action: 'update',
            module: 'tuition_payments',
            entity: $payment,
            oldValues: $oldValues,
            newValues: $payment->fresh()->toArray(),
            description: 'Updated tuition payment: Rp ' . number_format($payment->amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Tuition payment updated successfully',
            'data' => $payment->load([
                'student:id,name,nisn',
                'user:id,name,username',
            ]),
        ]);
    }

    public function destroy(string $id)
    {
        $payment = KeuanganSpp::findOrFail($id);

        $oldValues = $payment->toArray();
        $amount = $payment->amount;

        $payment->delete();

        ActivityLogService::log(
            action: 'delete',
            module: 'tuition_payments',
            entity: null,
            oldValues: $oldValues,
            newValues: null,
            description: 'Deleted tuition payment: Rp ' . number_format($amount, 0, ',', '.')
        );

        return response()->json([
            'message' => 'Tuition payment deleted successfully',
        ]);
    }
}
