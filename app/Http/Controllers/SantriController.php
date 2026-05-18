<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SantriController extends Controller
{
    public function index()
    {

        return response()->json(Santri::latest()->get());
    }

    public function create() {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'nama_wali' => 'required|string|max:255',
            'kontak_wali' => 'required|string|max:20',
            'alamat' => 'nullable|string',
        ]);

        $tanggalLahir = Carbon::parse($validated['tanggal_lahir']);
        $tanggalMasuk = $tanggalLahir->copy()->addYears(3);

        $status = now()->greaterThanOrEqualTo($tanggalMasuk)
            ? 'aktif'
            : 'pending';

        $validated['tanggal_masuk'] = $tanggalMasuk->format('Y-m-d');
        $validated['status'] = $status;

        $santri = Santri::create($validated);

        return response()->json([
            'message' => 'Santri berhasil ditambahkan',
            'data' => $santri,
        ], 201);
    }

    public function show(string $id)
    {
        $santri = Santri::findOrFail($id);

        return response()->json($santri);
    }

    public function edit(Santri $santri) {}

    public function update(Request $request, $id)
    {
        $santri = Santri::findOrFail($id);

        $validated = $request->validate([
            'nama'          => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'tanggal_lahir' => 'required|date',
            'nama_wali'     => 'required|string|max:255',
            'kontak_wali'   => 'required|string|max:20',
            'alamat'        => 'nullable|string',
            'status'        => 'nullable|in:pending,aktif,lulus,keluar',
        ]);

        $tanggalLahir = Carbon::parse($validated['tanggal_lahir']);
        $tanggalMasuk = $tanggalLahir->copy()->addYears(3);

        // Hitung status otomatis hanya jika status tidak diisi manual
        if (empty($validated['status'])) {
            $validated['status'] = now()->greaterThanOrEqualTo($tanggalMasuk)
                ? 'aktif'
                : 'pending';
        }

        // Jika status diset lulus/keluar, jangan override dengan otomatis
        $validated['tanggal_masuk'] = $tanggalMasuk->format('Y-m-d');

        $santri->update($validated);

        return response()->json([
            'message' => 'Data santri berhasil diperbarui',
            'data'    => $santri,
        ]);
    }

    public function destroy(string $id)
    {
        $santri = Santri::findOrFail($id);
        $santri->delete();

        return response()->json([
            'message' => 'Data santri berhasil dihapus',
        ]);
    }
}
