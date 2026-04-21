<?php

namespace App\Http\Controllers;

use App\Enums\AbsenceStatus;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class CommunityController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var User $viewer */
        $viewer = auth()->user();

        $users = User::query()
            ->with('area:id,name')
            ->where('is_active', true)
            ->when(! $viewer->isSuperAdmin(), function ($query) {
                $query->where('role', '!=', UserRole::SUPERADMIN->value);
            })
            ->withCount([
                'absences as pending_absences_count' => function ($query) {
                    $query->where('status', AbsenceStatus::PENDING->value);
                },
                'absences as current_absences_count' => function ($query) {
                    $query->where('status', AbsenceStatus::APPROVED->value)
                        ->whereDate('start_datetime', '<=', now())
                        ->whereDate('end_datetime', '>=', now());
                },
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'phone',
                'birth_date',
                'role',
                'photo_path',
                'area_id',
            ])
            ->map(function (User $user) {
                $status = 'disponible';
                if ($user->current_absences_count > 0) {
                    $status = 'vacaciones';
                } elseif ($user->pending_absences_count > 0) {
                    $status = 'pendiente';
                }

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'photo_url' => $user->photo_url,
                    'role' => $user->role instanceof UserRole ? $user->role->value : $user->role,
                    'role_label' => $user->role_name,
                    'area' => $user->area?->name,
                    'age' => $user->birth_date ? $user->birth_date->age : null,
                    'status' => $status,
                ];
            })
            ->values();

        return response()->json([
            'viewer' => [
                'id' => $viewer->id,
                'is_superadmin' => $viewer->isSuperAdmin(),
                'role' => $viewer->role instanceof UserRole ? $viewer->role->value : $viewer->role,
            ],
            'users' => $users,
        ]);
    }
}

