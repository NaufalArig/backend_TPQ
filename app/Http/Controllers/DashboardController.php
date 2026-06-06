<?php

namespace App\Http\Controllers;

use App\Models\Santri;
use App\Models\Guru;
use App\Models\User;
use App\Models\Kelas;
use App\Models\KeuanganSpp;
use App\Models\KeuanganPembangunan;
use App\Models\Notification;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $tuitionThisMonth = KeuanganSpp::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $developmentFundThisMonth = KeuanganPembangunan::whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $incomeThisMonth = $tuitionThisMonth + $developmentFundThisMonth;

        $totalTuition = KeuanganSpp::sum('amount');
        $totalDevelopmentFund = KeuanganPembangunan::sum('amount');
        $totalIncome = $totalTuition + $totalDevelopmentFund;

        $financeChart = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $tuition = KeuanganSpp::whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');

            $developmentFund = KeuanganPembangunan::whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');

            $financeChart[] = [
                'month_label' => $date->translatedFormat('M Y'),
                'tuition' => (float) $tuition,
                'development_fund' => (float) $developmentFund,
                'total_income' => (float) ($tuition + $developmentFund),
            ];
        }

        $latestTuitionPayments = KeuanganSpp::with([
            'student:id,name,nisn',
            'user:id,name,username',
        ])
            ->latest('payment_date')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'tuition',
                    'payment_date' => optional($item->payment_date)->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                    'note' => $item->note,
                    'student' => $item->student,
                    'financial_category' => null,
                    'user' => $item->user,
                    'created_at' => $item->created_at,
                ];
            });

        $latestDevelopmentFundPayments = KeuanganPembangunan::with([
            'financialCategory:id,name',
            'user:id,name,username',
        ])
            ->latest('payment_date')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'development_fund',
                    'payment_date' => optional($item->payment_date)->format('Y-m-d'),
                    'amount' => (float) $item->amount,
                    'note' => $item->note,
                    'student' => null,
                    'financial_category' => $item->financialCategory,
                    'user' => $item->user,
                    'created_at' => $item->created_at,
                ];
            });

        $latestTransactions = $latestTuitionPayments
            ->merge($latestDevelopmentFundPayments)
            ->sortByDesc(function ($item) {
                return $item['payment_date'] . ' ' . $item['created_at'];
            })
            ->values()
            ->take(5);

        return response()->json([
            'total_students' => Santri::count(),
            'active_students' => Santri::where('status', 'active')->count(),
            'pending_students' => Santri::where('status', 'pending')->count(),
            'graduated_students' => Santri::where('status', 'graduated')->count(),
            'left_students' => Santri::where('status', 'left')->count(),

            'total_teachers' => Guru::count(),
            'active_teachers' => Guru::where('status', 'active')->count(),
            'pending_teachers' => Guru::where('status', 'pending')->count(),
            'inactive_teachers' => Guru::where('status', 'inactive')->count(),

            'total_users' => User::count(),

            'total_study_classes' => Kelas::count(),
            'active_study_classes' => Kelas::where('status', 'active')->count(),

            'tuition_this_month' => (float) $tuitionThisMonth,
            'development_fund_this_month' => (float) $developmentFundThisMonth,
            'income_this_month' => (float) $incomeThisMonth,

            'total_tuition' => (float) $totalTuition,
            'total_development_fund' => (float) $totalDevelopmentFund,
            'total_income' => (float) $totalIncome,

            'unread_notifications' => Notification::where('is_read', false)->count(),

            'pending_students_list' => Santri::with('studyClass:id,name')
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get([
                    'id',
                    'study_class_id',
                    'name',
                    'birth_date',
                    'join_date',
                    'status',
                ]),

            'latest_transactions' => $latestTransactions,

            'finance_chart' => $financeChart,
        ]);
    }
}
