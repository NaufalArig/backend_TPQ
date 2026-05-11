<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('keuangans', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('keterangan')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('keuangans', function (Blueprint $table) {});
    }
};
