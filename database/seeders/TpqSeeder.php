<?php

namespace Database\Seeders;

use App\Models\Tpq;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TpqSeeder extends Seeder
{
    public function run(): void
    {
        $tpq1 = Tpq::firstOrCreate(
            ['name' => "TPQ Barokatul Qur'an"],
            [
                'address' => 'GMP Blok N, Piayu, Batam',
                'phone' => null,
                'head_name' => null,
                'status' => 'active',
            ]
        );

        $tpq2 = Tpq::firstOrCreate(
            ['name' => 'TPQ Tsaubatul Jannah'],
            [
                'address' => 'Perum. GMP Blok ENO. 60, Duriangkang, Sei Beduk, Batam',
                'phone' => null,
                'head_name' => null,
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['username' => 'admin_barokatul'],
            [
                'tpq_id' => $tpq1->id,
                'name' => 'Admin Barokatul',
                'email' => 'admin.barokatul@example.com',
                'password' => Hash::make(
                    env('DEFAULT_TPQ_PASSWORD', 'ChangeMe123!')
                ),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        User::updateOrCreate(
            ['username' => 'admin_tsaubatul'],
            [
                'tpq_id' => $tpq2->id,
                'name' => 'Admin Tsaubatul',
                'email' => 'admin.tsaubatul@example.com',
                'password' => Hash::make(
                    env('DEFAULT_TPQ_PASSWORD', 'ChangeMe123!')
                ),
                'role' => 'admin',
                'status' => 'active',
            ]
        );

        $tables = [
            'users',
            'students',
            'teachers',
            'study_classes',
            'student_attendances',
            'financial_categories',
            'tuition_payments',
            'development_fund_payments',
            'asset_categories',
            'assets',
            'notifications',
            'activity_logs',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) && DB::getSchemaBuilder()->hasColumn($table, 'tpq_id')) {
                DB::table($table)
                    ->whereNull('tpq_id')
                    ->update(['tpq_id' => $tpq1->id]);
            }
        }
    }
}
