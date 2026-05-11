<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keuangans', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->enum('jenis', ['pemasukan', 'pengeluaran']);
            $table->decimal('nominal', 15, 2);
            $table->string('keterangan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keuangans');
    }
};
