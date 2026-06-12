<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\UsesTpqScope;
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
    use UsesTpqScope;

    public function index()
    {
        $now = Carbon::now();
        $tpqId = $this->currentTpqId();

        $tuitionThisMonth = KeuanganSpp::where('tpq_id', $tpqId)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->sum('amount');

        $developmentIncomeThisMonth = KeuanganPembangunan::where('tpq_id', $tpqId)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->where('transaction_type', 'income')
            ->sum('amount');

        $developmentExpenseThisMonth = KeuanganPembangunan::where('tpq_id', $tpqId)
            ->whereMonth('payment_date', $now->month)
            ->whereYear('payment_date', $now->year)
            ->where('transaction_type', 'expense')
            ->sum('amount');

        $developmentFundThisMonth = $developmentIncomeThisMonth - $developmentExpenseThisMonth;
        $incomeThisMonth = $tuitionThisMonth + $developmentFundThisMonth;

        $totalTuition = KeuanganSpp::where('tpq_id', $tpqId)
            ->sum('amount');

        $totalDevelopmentIncome = KeuanganPembangunan::where('tpq_id', $tpqId)
            ->where('transaction_type', 'income')
            ->sum('amount');

        $totalDevelopmentExpense = KeuanganPembangunan::where('tpq_id', $tpqId)
            ->where('transaction_type', 'expense')
            ->sum('amount');

        $totalDevelopmentFund = $totalDevelopmentIncome - $totalDevelopmentExpense;
        $totalIncome = $totalTuition + $totalDevelopmentFund;

        $financeChart = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $tuition = KeuanganSpp::where('tpq_id', $tpqId)
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->sum('amount');

            $developmentIncome = KeuanganPembangunan::where('tpq_id', $tpqId)
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->where('transaction_type', 'income')
                ->sum('amount');

            $developmentExpense = KeuanganPembangunan::where('tpq_id', $tpqId)
                ->whereMonth('payment_date', $date->month)
                ->whereYear('payment_date', $date->year)
                ->where('transaction_type', 'expense')
                ->sum('amount');

            $developmentFund = $developmentIncome - $developmentExpense;

            $financeChart[] = [
                'month_label' => $date->translatedFormat('M Y'),
                'tuition' => (float) $tuition,
                'development_fund' => (float) $developmentFund,
                'total_income' => (float) ($tuition + $developmentFund),
            ];
        }

        $latestTuitionPayments = KeuanganSpp::with([
            'student:id,tpq_id,name,nisn',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $tpqId)
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
            'financialCategory:id,tpq_id,name',
            'user:id,tpq_id,name,username',
        ])
            ->where('tpq_id', $tpqId)
            ->latest('payment_date')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'type' => 'development_fund',
                    'transaction_type' => $item->transaction_type,
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

        $user = auth()->user();

        $teacherDashboard = null;

        if ($user->role === 'teacher') {
            $teacher = Guru::where('tpq_id', $tpqId)
                ->where('user_id', $user->id)
                ->first();

            $teacherClassIds = $teacher
                ? Kelas::where('tpq_id', $tpqId)
                ->where('teacher_id', $teacher->id)
                ->pluck('id')
                : collect();

            $teacherClasses = Kelas::with([
                'santris' => function ($query) use ($tpqId) {
                    $query->select('id', 'tpq_id', 'study_class_id', 'name', 'status', 'join_date')
                        ->where('tpq_id', $tpqId)
                        ->latest();
                },
            ])
                ->withCount([
                    'santris as students_count' => function ($query) use ($tpqId) {
                        $query->where('tpq_id', $tpqId)
                            ->where('status', 'active');
                    },
                ])
                ->where('tpq_id', $tpqId)
                ->whereIn('id', $teacherClassIds)
                ->latest()
                ->get();

            $latestAttendances = \App\Models\AbsensiSantri::with([
                'student:id,tpq_id,study_class_id,name',
                'student.studyClass:id,tpq_id,name',
            ])
                ->where('tpq_id', $tpqId)
                ->whereHas('student', function ($query) use ($tpqId, $teacherClassIds) {
                    $query->where('tpq_id', $tpqId)
                        ->whereIn('study_class_id', $teacherClassIds);
                })
                ->latest('attendance_date')
                ->take(5)
                ->get();

            $teacherDashboard = [
                'total_classes' => $teacherClasses->count(),
                'total_students' => $teacherClasses->sum('students_count'),
                'classes' => $teacherClasses,
                'latest_attendances' => $latestAttendances,
            ];
        }

        $financeDashboard = null;

        if ($user->role === 'treasurer') {
            $financeDashboard = [
                'tuition_this_month' => (float) $tuitionThisMonth,
                'development_fund_this_month' => (float) $developmentFundThisMonth,
                'income_this_month' => (float) $incomeThisMonth,
                'total_tuition' => (float) $totalTuition,
                'total_development_fund' => (float) $totalDevelopmentFund,
                'total_income' => (float) $totalIncome,
                'latest_transactions' => $latestTransactions,
                'finance_chart' => $financeChart,
            ];
        }

        return response()->json([
            'total_students' => Santri::where('tpq_id', $tpqId)->count(),
            'active_students' => Santri::where('tpq_id', $tpqId)->where('status', 'active')->count(),
            'pending_students' => Santri::where('tpq_id', $tpqId)->where('status', 'pending')->count(),
            'graduated_students' => Santri::where('tpq_id', $tpqId)->where('status', 'graduated')->count(),
            'left_students' => Santri::where('tpq_id', $tpqId)->where('status', 'left')->count(),

            'total_teachers' => Guru::where('tpq_id', $tpqId)->count(),
            'active_teachers' => Guru::where('tpq_id', $tpqId)->where('status', 'active')->count(),
            'pending_teachers' => Guru::where('tpq_id', $tpqId)->where('status', 'pending')->count(),
            'inactive_teachers' => Guru::where('tpq_id', $tpqId)->where('status', 'inactive')->count(),

            'total_users' => User::where('tpq_id', $tpqId)->count(),

            'total_study_classes' => Kelas::where('tpq_id', $tpqId)->count(),
            'active_study_classes' => Kelas::where('tpq_id', $tpqId)->where('status', 'active')->count(),

            'tuition_this_month' => (float) $tuitionThisMonth,
            'development_fund_this_month' => (float) $developmentFundThisMonth,
            'income_this_month' => (float) $incomeThisMonth,

            'total_tuition' => (float) $totalTuition,
            'total_development_fund' => (float) $totalDevelopmentFund,
            'total_income' => (float) $totalIncome,

            'unread_notifications' => Notification::where('tpq_id', $tpqId)
                ->where('is_read', false)
                ->count(),

            'role' => $user->role,

            'teacher_dashboard' => $teacherDashboard,
            'finance_dashboard' => $financeDashboard,

            'pending_students_list' => Santri::with('studyClass:id,name')
                ->where('tpq_id', $tpqId)
                ->where('status', 'pending')
                ->latest()
                ->take(5)
                ->get([
                    'id',
                    'tpq_id',
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
