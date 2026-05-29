<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class GuruController extends Controller
{
    public function index()
    {
        return response()->json(Guru::latest()->get());
    }

    public function create() {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'alamat' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'kontak' => 'required|string|max:20',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('guru', 'public');
        }

        $tanggalMasuk = \Carbon\Carbon::parse($validated['tanggal_masuk']);

        $validated['status'] = $tanggalMasuk->isFuture()
            ? 'pending'
            : 'aktif';

        $guru = DB::transaction(function () use ($validated) {

            $user = User::create([
                'name' => $validated['nama'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'guru',
            ]);

            unset($validated['email'], $validated['password']);

            return Guru::create([
                ...$validated,
                'user_id' => $user->id,
                'notifikasi_usia' => false,
            ]);
        });

        return response()->json([
            'message' => 'Data guru berhasil ditambahkan',
            'data' => $guru,
        ], 201);
    }

    public function show(string $id)
    {
        $guru = Guru::findOrFail($id);

        return response()->json($guru);
    }

    public function edit(Guru $guru) {}

    public function update(Request $request, Guru $guru)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'kontak' => 'required|string|max:20',
            'tanggal_masuk' => 'required|date',
            'tanggal_keluar' => 'nullable|date',
            'foto' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('foto')) {
            $validated['foto'] = $request->file('foto')->store('guru', 'public');
        }

        $tanggalMasuk = \Carbon\Carbon::parse($validated['tanggal_masuk']);
        $tanggalKeluar = !empty($validated['tanggal_keluar'])
            ? \Carbon\Carbon::parse($validated['tanggal_keluar'])
            : null;

        if ($tanggalKeluar && $tanggalKeluar->lte(now())) {
            $validated['status'] = 'nonaktif';
        } elseif ($tanggalMasuk->isFuture()) {
            $validated['status'] = 'pending';
        } else {
            $validated['status'] = 'aktif';
        }

        $guru->update($validated);

        return response()->json([
            'message' => 'Data guru berhasil diperbarui',
            'data' => $guru,
        ]);
    }


    public function destroy(string $id)
    {
        $guru = Guru::findOrFail($id);
        $guru->delete();

        return response()->json([
            'message' => 'Data guru berhasil dihapus',
        ]);
    }
}
