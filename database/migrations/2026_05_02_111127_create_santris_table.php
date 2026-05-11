<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('santris', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->enum('jenis_kelamin', ['L', 'P']);
        $table->date('tanggal_lahir');
        $table->string('nama_wali');
        $table->string('kontak_wali');
        $table->text('alamat');
        $table->date('tanggal_masuk');
        $table->enum('status', ['pending', 'aktif', 'lulus', 'keluar'])->default('pending');
        $table->boolean('notifikasi_usia')->default(false);
        $table->timestamps();
    });
}
};
