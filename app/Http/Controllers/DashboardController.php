<?php

namespace App\Http\Controllers;

use App\Enums\AbsenceStatus;
use App\Enums\UserRole;
use App\Models\Absence;
use App\Models\Area;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Models\VacationYear;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $isAdmin = $user->isAdmin();
        $isSuperAdmin = $user->isSuperAdmin();

        $selectedUserId = $isAdmin ? $this->nullableInt('user_id') : $user->id;
        $selectedAreaId = $this->nullableInt('area_id');

        $response = [
            'viewer' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $this->roleValue($user),
                'is_admin' => $isAdmin,
                'is_superadmin' => $isSuperAdmin,
            ],
            'metrics' => [
                'personal' => $this->getPersonalMetrics($user),
                'organization' => $isAdmin ? $this->getOrganizationMetrics($selectedUserId, $selectedAreaId) : null,
                'superadmin' => $isSuperAdmin ? $this->getSuperAdminMetrics() : null,
            ],
            'filters' => [
                'selected_user_id' => $selectedUserId,
                'selected_area_id' => $selectedAreaId,
                'users' => $isAdmin ? $this->getUsersList() : [],
                'areas' => $this->getAreasList(),
            ],
            'tables' => [
                'employee_statuses' => $this->getEmployeeStatuses($selectedAreaId),
                'recent_absences' => $this->getRecentAbsences($user, $selectedUserId, $selectedAreaId),
                'pending_approvals' => $this->getPendingApprovals($user, $selectedUserId, $selectedAreaId),
                'vacation_balances' => $isAdmin
                    ? $this->getVacationBalances($selectedUserId, $selectedAreaId)
                    : null,
                'my_expiring_vacations' => ! $isAdmin
                    ? $this->getMyExpiringVacations($user)
                    : null,
            ],
            'subscription' => ($isAdmin && ! $isSuperAdmin) ? $this->getSubscriptionInfo($user) : null,
        ];

        return response()->json($response);
    }

    protected function getAreasList(): Collection
    {
        return Area::active()
            ->ordered()
            ->get(['id', 'name', 'color']);
    }

    protected function getUsersList(): Collection
    {
        return User::query()
            ->where('role', UserRole::COLLABORATOR->value)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'identification', 'email']);
    }

    protected function getPersonalMetrics(User $user): array
    {
        $availableVacationDays = VacationYear::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>=', now())
            ->get()
            ->sum(fn (VacationYear $year) => $year->allocated_days - $year->used_days);

        $usedVacationDays = VacationYear::query()
            ->where('user_id', $user->id)
            ->whereYear('year', now()->year)
            ->sum('used_days');

        $pendingAbsences = Absence::query()
            ->where('user_id', $user->id)
            ->where('status', AbsenceStatus::PENDING->value)
            ->count();

        $approvedThisMonth = Absence::query()
            ->where('user_id', $user->id)
            ->where('status', AbsenceStatus::APPROVED->value)
            ->whereMonth('start_datetime', now()->month)
            ->whereYear('start_datetime', now()->year)
            ->count();

        $upcomingExpirations = VacationYear::query()
            ->where('user_id', $user->id)
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->whereColumn('used_days', '<', 'allocated_days')
            ->count();

        return [
            'available_vacation_days' => round($availableVacationDays, 1),
            'used_vacation_days' => round($usedVacationDays, 1),
            'pending_absences' => $pendingAbsences,
            'approved_this_month' => $approvedThisMonth,
            'upcoming_expirations' => $upcomingExpirations,
        ];
    }

    protected function getOrganizationMetrics(?int $selectedUserId, ?int $selectedAreaId): array
    {
        $employeeQuery = User::query()
            ->where('role', UserRole::COLLABORATOR->value)
            ->where('is_active', true);

        $absenceQuery = Absence::query();
        $vacationQuery = VacationYear::query();

        if ($selectedUserId) {
            $employeeQuery->where('id', $selectedUserId);
            $absenceQuery->where('user_id', $selectedUserId);
            $vacationQuery->where('user_id', $selectedUserId);
        }

        if ($selectedAreaId) {
            $employeeQuery->where('area_id', $selectedAreaId);

            $absenceQuery->whereHas('user', function ($query) use ($selectedAreaId) {
                $query->where('area_id', $selectedAreaId);
            });

            $vacationQuery->whereHas('user', function ($query) use ($selectedAreaId) {
                $query->where('area_id', $selectedAreaId);
            });
        }

        $totalEmployees = (clone $employeeQuery)->count();

        $pendingAbsences = (clone $absenceQuery)
            ->where('status', AbsenceStatus::PENDING->value)
            ->count();

        $approvedThisMonth = (clone $absenceQuery)
            ->where('status', AbsenceStatus::APPROVED->value)
            ->whereMonth('start_datetime', now()->month)
            ->whereYear('start_datetime', now()->year)
            ->count();

        $totalVacationDays = (clone $vacationQuery)
            ->where('expires_at', '>=', now())
            ->get()
            ->sum(fn (VacationYear $year) => $year->allocated_days - $year->used_days);

        $upcomingExpirations = (clone $vacationQuery)
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->whereColumn('used_days', '<', 'allocated_days')
            ->count();

        $employeeIds = (clone $employeeQuery)->pluck('id');

        $unavailableNow = Absence::query()
            ->whereIn('user_id', $employeeIds)
            ->where('status', AbsenceStatus::APPROVED->value)
            ->whereDate('start_datetime', '<=', now())
            ->whereDate('end_datetime', '>=', now())
            ->distinct('user_id')
            ->count('user_id');

        return [
            'total_employees' => $totalEmployees,
            'pending_absences' => $pendingAbsences,
            'approved_this_month' => $approvedThisMonth,
            'total_vacation_days' => round($totalVacationDays, 1),
            'upcoming_expirations' => $upcomingExpirations,
            'unavailable_now' => $unavailableNow,
        ];
    }

    protected function getSuperAdminMetrics(): array
    {
        $totalAdmins = User::query()
            ->withoutGlobalScopes()
            ->where('role', UserRole::ADMIN->value)
            ->where('is_active', true)
            ->count();

        $totalSuperAdmins = User::query()
            ->withoutGlobalScopes()
            ->where('role', UserRole::SUPERADMIN->value)
            ->where('is_active', true)
            ->count();

        $activeTenants = Tenant::query()
            ->where('is_active', true)
            ->count();

        $activeSubscriptions = Subscription::query()
            ->where('is_active', true)
            ->where('expires_at', '>=', now())
            ->count();

        $subscriptionsExpiringSoon = Subscription::query()
            ->where('is_active', true)
            ->whereBetween('expires_at', [now(), now()->addDays(30)])
            ->count();

        return [
            'total_admins' => $totalAdmins,
            'total_superadmins' => $totalSuperAdmins,
            'active_tenants' => $activeTenants,
            'active_subscriptions' => $activeSubscriptions,
            'subscriptions_expiring_30d' => $subscriptionsExpiringSoon,
        ];
    }

    protected function getEmployeeStatuses(?int $selectedAreaId): array
    {
        $search = trim((string) request()->get('status_search', ''));

        $employeesQuery = User::query()
            ->where('role', UserRole::COLLABORATOR->value)
            ->where('is_active', true)
            ->orderBy('name');

        if ($selectedAreaId) {
            $employeesQuery->where('area_id', $selectedAreaId);
        }

        if ($search !== '') {
            $employeesQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('identification', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $employeesQuery->paginate(
            $this->perPage('status_per_page', 5),
            ['id', 'name'],
            'status_page'
        );

        $employeeIds = collect($paginator->items())->pluck('id');

        $relevantAbsences = Absence::query()
            ->with('type:id,name')
            ->whereIn('user_id', $employeeIds)
            ->where('status', AbsenceStatus::APPROVED->value)
            ->where(function ($query) {
                $query
                    ->where(function ($activeQuery) {
                        $activeQuery
                            ->whereDate('start_datetime', '<=', now())
                            ->whereDate('end_datetime', '>=', now());
                    })
                    ->orWhere(function ($upcomingQuery) {
                        $upcomingQuery
                            ->whereMonth('start_datetime', now()->month)
                            ->whereYear('start_datetime', now()->year)
                            ->whereDate('start_datetime', '>', now());
                    });
            })
            ->orderBy('start_datetime')
            ->get()
            ->groupBy('user_id');

        $statuses = collect($paginator->items())->map(function (User $employee) use ($relevantAbsences) {
            $employeeAbsences = $relevantAbsences->get($employee->id, collect());

            $currentAbsence = $employeeAbsences->first(function (Absence $absence) {
                return $absence->start_datetime->lte(now())
                    && $absence->end_datetime->gte(now());
            });

            $upcomingAbsence = $employeeAbsences->first(function (Absence $absence) {
                return $absence->start_datetime->gt(now())
                    && $absence->start_datetime->month === now()->month
                    && $absence->start_datetime->year === now()->year;
            });

            $status = 'green';
            if ($currentAbsence) {
                $status = 'red';
            } elseif ($upcomingAbsence) {
                $status = 'yellow';
            }

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'status' => $status,
                'current_absence' => $currentAbsence ? [
                    'type' => $currentAbsence->type?->name,
                    'end' => $currentAbsence->end_datetime->toDateString(),
                ] : null,
                'upcoming_absence' => $upcomingAbsence ? [
                    'type' => $upcomingAbsence->type?->name,
                    'start' => $upcomingAbsence->start_datetime->toDateString(),
                ] : null,
            ];
        });

        $summary = [
            'green' => $statuses->where('status', 'green')->count(),
            'yellow' => $statuses->where('status', 'yellow')->count(),
            'red' => $statuses->where('status', 'red')->count(),
            'total' => $paginator->total(),
        ];

        return [
            'data' => $statuses->values(),
            'meta' => $this->paginationMeta($paginator),
            'summary' => $summary,
            'search' => $search,
        ];
    }

    protected function getRecentAbsences(User $viewer, ?int $selectedUserId, ?int $selectedAreaId): array
    {
        $search = trim((string) request()->get('recent_search', ''));

        $query = Absence::query()
            ->with(['user:id,name,area_id', 'type:id,name'])
            ->where('status', AbsenceStatus::APPROVED->value)
            ->orderByDesc('start_datetime');

        if (! $viewer->isAdmin()) {
            $query->where('user_id', $viewer->id);
        } else {
            if ($selectedUserId) {
                $query->where('user_id', $selectedUserId);
            }

            if ($selectedAreaId) {
                $query->whereHas('user', function ($builder) use ($selectedAreaId) {
                    $builder->where('area_id', $selectedAreaId);
                });
            }
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('identification', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('type', function ($typeQuery) use ($search) {
                        $typeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->paginate(
            $this->perPage('recent_per_page', 5),
            ['*'],
            'recent_page'
        );

        $data = collect($paginator->items())->map(function (Absence $absence) {
            return [
                'id' => $absence->id,
                'user' => [
                    'id' => $absence->user->id,
                    'name' => $absence->user->name,
                ],
                'type' => [
                    'name' => $absence->type?->name,
                ],
                'start' => $absence->start_datetime->toDateString(),
                'end' => $absence->end_datetime->toDateString(),
                'days' => $absence->total_days,
            ];
        })->values();

        return [
            'data' => $data,
            'meta' => $this->paginationMeta($paginator),
            'search' => $search,
        ];
    }

    protected function getPendingApprovals(User $viewer, ?int $selectedUserId, ?int $selectedAreaId): array
    {
        $search = trim((string) request()->get('pending_search', ''));

        $query = Absence::query()
            ->with(['user:id,name,identification,email,area_id', 'type:id,name'])
            ->where('status', AbsenceStatus::PENDING->value)
            ->orderBy('start_datetime');

        if (! $viewer->isAdmin()) {
            $query->where('user_id', $viewer->id);
        } else {
            if ($selectedUserId) {
                $query->where('user_id', $selectedUserId);
            }

            if ($selectedAreaId) {
                $query->whereHas('user', function ($builder) use ($selectedAreaId) {
                    $builder->where('area_id', $selectedAreaId);
                });
            }
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('identification', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('type', function ($typeQuery) use ($search) {
                        $typeQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->paginate(
            $this->perPage('pending_per_page', 5),
            ['*'],
            'pending_page'
        );

        $data = collect($paginator->items())->map(function (Absence $absence) {
            return [
                'id' => $absence->id,
                'user' => [
                    'id' => $absence->user->id,
                    'name' => $absence->user->name,
                ],
                'type' => [
                    'name' => $absence->type?->name,
                ],
                'start' => $absence->start_datetime->toDateString(),
                'end' => $absence->end_datetime->toDateString(),
                'days' => $absence->total_days,
                'requested_at' => $absence->created_at->diffForHumans(),
            ];
        })->values();

        return [
            'data' => $data,
            'meta' => $this->paginationMeta($paginator),
            'search' => $search,
        ];
    }

    protected function getVacationBalances(?int $selectedUserId, ?int $selectedAreaId): array
    {
        $search = trim((string) request()->get('vacation_search', ''));

        $query = User::query()
            ->where('role', UserRole::COLLABORATOR->value)
            ->where('is_active', true)
            ->with([
                'vacationYears' => function ($vacationQuery) {
                    $vacationQuery->where('expires_at', '>=', now());
                },
            ])
            ->orderBy('name');

        if ($selectedUserId) {
            $query->where('id', $selectedUserId);
        }

        if ($selectedAreaId) {
            $query->where('area_id', $selectedAreaId);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('identification', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $paginator = $query->paginate(
            $this->perPage('vacation_per_page', 5),
            ['id', 'name', 'identification', 'email'],
            'vacation_page'
        );

        $data = collect($paginator->items())->map(function (User $user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'available' => round($user->availableVacationDays(), 1),
                'expiring_soon' => $user->vacationYears
                    ->filter(fn (VacationYear $year) => $year->expires_at->diffInDays(now()) <= 30)
                    ->map(fn (VacationYear $year) => [
                        'year' => $year->year,
                        'days' => round($year->availableDays(), 1),
                        'expires' => $year->expires_at->toDateString(),
                    ])
                    ->values(),
            ];
        })->values();

        return [
            'data' => $data,
            'meta' => $this->paginationMeta($paginator),
            'search' => $search,
        ];
    }

    protected function getMyExpiringVacations(User $user): array
    {
        return VacationYear::query()
            ->where('user_id', $user->id)
            ->whereBetween('expires_at', [now(), now()->addDays(90)])
            ->whereColumn('used_days', '<', 'allocated_days')
            ->orderBy('expires_at')
            ->get()
            ->map(fn (VacationYear $year) => [
                'id' => $year->id,
                'year' => $year->year,
                'available_days' => round($year->availableDays(), 1),
                'expires_at' => $year->expires_at->toDateString(),
            ])
            ->values()
            ->all();
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

    protected function roleValue(User $user): string
    {
        return $user->role instanceof UserRole
            ? $user->role->value
            : (string) $user->role;
    }

    protected function nullableInt(string $key): ?int
    {
        $value = request()->get($key);

        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected function perPage(string $key, int $default = 5): int
    {
        $value = (int) request()->get($key, $default);

        return max(5, min(10, $value));
    }

    protected function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
