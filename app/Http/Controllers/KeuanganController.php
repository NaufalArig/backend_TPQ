<?php

namespace App\Http\Controllers;

use App\Models\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KeuanganController extends Controller
{
    public function index()
    {
        $data = Keuangan::with('user:id,name')
            ->orderBy('tanggal', 'desc')
            ->get();

        $pemasukan  = $data->where('jenis', 'pemasukan')->sum('nominal');
        $pengeluaran = $data->where('jenis', 'pengeluaran')->sum('nominal');

        return response()->json([
            'data'        => $data,
            'pemasukan'   => $pemasukan,
            'pengeluaran' => $pengeluaran,
            'saldo'       => $pemasukan - $pengeluaran,
        ]);
    }

    public function create() {}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'    => 'required|date',
            'jenis'      => 'required|in:pemasukan,pengeluaran',
            'nominal'    => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $keuangan = Keuangan::create([
            ...$request->only(['tanggal', 'jenis', 'nominal', 'keterangan']),
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Transaksi berhasil ditambahkan',
            'data'    => $keuangan->load('user:id,name'),
        ], 201);
    }

    public function show(Keuangan $keuangan)
    {
        return response()->json($keuangan->load('user:id,name'));
    }

    public function edit(Keuangan $keuangan) {}

    public function update(Request $request, Keuangan $keuangan)
    {
        $validator = Validator::make($request->all(), [
            'tanggal'    => 'required|date',
            'jenis'      => 'required|in:pemasukan,pengeluaran',
            'nominal'    => 'required|numeric|min:0',
            'keterangan' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $keuangan->update($request->only(['tanggal', 'jenis', 'nominal', 'keterangan']));

        return response()->json([
            'message' => 'Transaksi berhasil diperbarui',
            'data'    => $keuangan->load('user:id,name'),
        ]);
    }


    public function destroy(Keuangan $keuangan)
    {
        $keuangan->delete();

        return response()->json(['message' => 'Transaksi berhasil dihapus']);
    }
}
