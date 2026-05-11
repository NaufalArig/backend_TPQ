<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('santri_id')->constrained()->onDelete('cascade');
            $table->string('judul');
            $table->text('pesan');
            $table->boolean('dibaca')->default(false);
            $table->timestamps();
        });
    }
};
