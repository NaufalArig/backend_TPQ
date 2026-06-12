<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('study_classes') && !Schema::hasColumn('study_classes', 'teacher_id')) {
            Schema::table('study_classes', function (Blueprint $table) {
                $table->foreignId('teacher_id')
                    ->nullable()
                    ->after('tpq_id')
                    ->constrained('teachers')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('assets') && !Schema::hasColumn('assets', 'user_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('tpq_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('assets') && Schema::hasColumn('assets', 'user_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasTable('study_classes') && Schema::hasColumn('study_classes', 'teacher_id')) {
            Schema::table('study_classes', function (Blueprint $table) {
                $table->dropConstrainedForeignId('teacher_id');
            });
        }
    }
};
