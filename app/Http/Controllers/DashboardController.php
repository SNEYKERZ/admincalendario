<?php

namespace App\Http\Controllers;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\User;
use App\Models\VacationYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        // Si no es admin, solo puede ver sus propios datos
        $userId = ! $isAdmin ? $user->id : request()->get('user_id');
        $search = request()->get('search');

        return response()->json([
            'metrics' => $this->getMetrics($userId, $isAdmin),
            'chartData' => $this->getChartData($userId, $isAdmin),
            'recentAbsences' => $this->getRecentAbsences($userId, $search, $isAdmin),
            'pendingApprovals' => $this->getPendingApprovals($userId, $search, $isAdmin),
            'vacationBalance' => $this->getVacationBalance($userId, $search, $isAdmin),
            'users' => $this->getUsersList($isAdmin),
            'is_admin' => $isAdmin,
            'current_user_id' => $user->id,
            // Only show subscription to admins (not superadmin)
            'subscription' => ($isAdmin && ! $user->isSuperAdmin()) ? $this->getSubscriptionInfo($user) : null,
        ]);
    }

    protected function getUsersList(bool $isAdmin): \Illuminate\Database\Eloquent\Collection
    {
        $query = User::select('id', 'name', 'identification', 'email');

        // Los colaboradores solo ven la lista de usuarios para filtro
        if (! $isAdmin) {
            $userId = auth()->id();

            return $query->where('id', $userId)->get();
        }

        return $query->whereNotIn('role', ['admin', 'superadmin'])
            ->orderBy('name')
            ->get();
    }

    protected function getMetrics(?int $userId = null, bool $isAdmin = true): array
    {
        $employeeQuery = User::whereNotIn('role', ['admin', 'superadmin']);
        $absenceQuery = Absence::query();
        $vacationQuery = VacationYear::query();

        if ($userId) {
            $employeeQuery->where('id', $userId);
            $absenceQuery->where('user_id', $userId);
            $vacationQuery->where('user_id', $userId);
        }

        $totalEmployees = $employeeQuery->count();

        $pendingAbsences = $absenceQuery->where('status', AbsenceStatus::PENDING->value)->count();

        $approvedThisMonth = $absenceQuery->clone()
            ->where('status', AbsenceStatus::APPROVED->value)
            ->whereMonth('start_datetime', now()->month)
            ->whereYear('start_datetime', now()->year)
            ->count();

        $totalVacationDays = $vacationQuery->clone()
            ->where('expires_at', '>=', now())
            ->get()
            ->sum(fn ($y) => $y->allocated_days - $y->used_days);

        $usedVacationDays = $vacationQuery->clone()
            ->whereYear('year', now()->year)
            ->get()
            ->sum('used_days');

        $upcomingExpirations = $vacationQuery->clone()
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->where('used_days', '<', 'allocated_days')
            ->count();

        return [
            'total_employees' => $totalEmployees,
            'pending_absences' => $pendingAbsences,
            'approved_this_month' => $approvedThisMonth,
            'total_vacation_days' => round($totalVacationDays, 1),
            'used_vacation_days' => round($usedVacationDays, 1),
            'upcoming_expirations' => $upcomingExpirations,
        ];
    }

    protected function getChartData(?int $userId = null): array
    {
        $absenceQuery = Absence::query();
        if ($userId) {
            $absenceQuery->where('user_id', $userId);
        }

        // Ausencias por mes (últimos 12 meses)
        $monthlyAbsences = $absenceQuery->clone()
            ->select(
                DB::raw('MONTH(start_datetime) as month'),
                DB::raw('YEAR(start_datetime) as year'),
                DB::raw('COUNT(*) as count')
            )
            ->where('start_datetime', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $monthlyData = collect(range(0, 11))->map(function ($i) use ($monthlyAbsences) {
            $date = now()->subMonths(11 - $i);
            $found = $monthlyAbsences->first(fn ($a) => $a->year == $date->year && $a->month == $date->month
            );

            return [
                'month' => $date->format('M'),
                'count' => $found?->count ?? 0,
            ];
        });

        // Distribución por tipo
        $byType = $absenceQuery->clone()
            ->select('absence_types.name', DB::raw('COUNT(*) as count'))
            ->join('absence_types', 'absences.absence_type_id', '=', 'absence_types.id')
            ->where('absences.start_datetime', '>=', now()->subYear())
            ->groupBy('absence_types.name')
            ->get();

        // Estado de ausencias
        $byStatus = $absenceQuery->clone()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('start_datetime', '>=', now()->subYear())
            ->groupBy('status')
            ->get();

        return [
            'monthly' => $monthlyData,
            'by_type' => $byType->map(fn ($i) => ['name' => $i->name, 'count' => $i->count]),
            'by_status' => $byStatus->map(fn ($i) => ['status' => $i->status, 'count' => $i->count]),
        ];
    }

    protected function getRecentAbsences(?int $userId = null, ?string $search = null)
    {
        $query = Absence::with(['user', 'type'])
            ->where('status', AbsenceStatus::APPROVED->value);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identification', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderByDesc('start_datetime')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'user' => ['id' => $a->user->id, 'name' => $a->user->name],
                'type' => ['name' => $a->type->name],
                'start' => $a->start_datetime->toDateString(),
                'end' => $a->end_datetime->toDateString(),
                'days' => $a->total_days,
            ]);
    }

    protected function getPendingApprovals(?int $userId = null, ?string $search = null)
    {
        $query = Absence::with(['user', 'type'])
            ->where('status', AbsenceStatus::PENDING->value);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identification', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('start_datetime')
            ->limit(10)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'user' => ['id' => $a->user->id, 'name' => $a->user->name],
                'type' => ['name' => $a->type->name],
                'start' => $a->start_datetime->toDateString(),
                'end' => $a->end_datetime->toDateString(),
                'days' => $a->total_days,
                'requested_at' => $a->created_at->diffForHumans(),
            ]);
    }

    protected function getVacationBalance(?int $userId = null, ?string $search = null)
    {
        $query = User::whereNotIn('role', ['admin', 'superadmin'])
            ->with(['vacationYears' => fn ($q) => $q->where('expires_at', '>=', now())]);

        if ($userId) {
            $query->where('id', $userId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('identification', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return $query
            ->get()
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'available' => $user->availableVacationDays(),
                'expiring_soon' => $user->vacationYears
                    ->filter(fn ($y) => $y->expires_at->diffInDays(now()) <= 30)
                    ->map(fn ($y) => [
                        'year' => $y->year,
                        'days' => $y->availableDays(),
                        'expires' => $y->expires_at->toDateString(),
                    ])
                    ->values(),
            ])
            ->sortByDesc('available')
            ->take(10)
            ->values();
    }

    protected function getSubscriptionInfo(User $user): ?array
    {
        $subscription = $user->subscriptions()
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->with('plan')
            ->first();

        if (! $subscription) {
            return [
                'has_subscription' => false,
                'show_ad' => false,
            ];
        }

        $settings = \App\Models\SubscriptionSettings::first();
        $daysThreshold = $settings?->show_ads_days_before ?? 5;
        $daysRemaining = $subscription->daysRemaining();

        return [
            'has_subscription' => true,
            'is_active' => $subscription->is_active,
            'plan_name' => $subscription->plan?->name,
            'starts_at' => $subscription->starts_at->toDateString(),
            'expires_at' => $subscription->expires_at->toDateString(),
            'days_remaining' => $daysRemaining,
            'show_ad' => $daysRemaining <= $daysThreshold,
        ];
    }
}
