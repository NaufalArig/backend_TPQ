<?php

namespace App\Http\Controllers;

use App\Models\AbsensiSantri;
use App\Models\Santri;
use Illuminate\Http\Request;

class AbsensiSantriController extends Controller
{
    public function index(Request $request)
    {
        $tanggal = $request->query('tanggal', now()->toDateString());

        $santri = Santri::where('status', 'aktif')
            ->orderBy('nama')
            ->get();

        $absensi = AbsensiSantri::where('tanggal', $tanggal)
            ->get()
            ->keyBy('santri_id');

        $data = $santri->map(function ($item) use ($absensi) {
            $absen = $absensi->get($item->id);

            return [
                'santri_id' => $item->id,
                'nama' => $item->nama,
                'tanggal' => $absen?->tanggal,
                'status' => $absen?->status ?? 'hadir',
                'keterangan' => $absen?->keterangan ?? '',
            ];
        });

        return response()->json([
            'tanggal' => $tanggal,
            'data' => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => 'required|date',
            'absensi' => 'required|array',
            'absensi.*.santri_id' => 'required|exists:santris,id',
            'absensi.*.status' => 'required|in:hadir,izin,sakit,alpa',
            'absensi.*.keterangan' => 'nullable|string',
        ]);

        foreach ($validated['absensi'] as $item) {
            AbsensiSantri::updateOrCreate(
                [
                    'santri_id' => $item['santri_id'],
                    'tanggal' => $validated['tanggal'],
                ],
                [
                    'user_id' => auth()->id(),
                    'status' => $item['status'],
                    'keterangan' => $item['keterangan'] ?? null,
                ]
            );
        }

        return response()->json([
            'message' => 'Absensi berhasil disimpan',
        ]);
    }

    public function riwayat(Request $request)
    {
        $query = AbsensiSantri::with(['santri:id,nama', 'user:id,name'])
            ->latest('tanggal');

        if ($request->tanggal) {
            $query->whereDate('tanggal', $request->tanggal);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        return response()->json($query->get());
    }
}
