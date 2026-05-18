<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GuruController extends Controller
{
    public function index()
    {
        return response()->json(Guru::latest()->get());
    }

    public function create() {}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'tanggal_masuk' => 'required|date',
            'kontak' => 'required|string|max:20',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $guru = Guru::create([
            ...$request->all(),
            'notifikasi_usia' => false,
        ]);

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

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'alamat' => 'required|string',
            'kontak' => 'required|string|max:20',
            'tanggal_masuk' => 'required|date',
            'tanggal_keluar' => 'nullable|date',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $guru->update($request->all());

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
