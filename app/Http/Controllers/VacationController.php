<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $user = auth()->user();

        // Get users - exclude superadmin for admins, exclude both admin and superadmin for regular users
        $query = User::with(['vacationYears', 'area']);

        if ($user->isSuperAdmin()) {
            // Superadmin sees everyone except themselves (handled by UI)
            $query->where('role', '!=', \App\Enums\UserRole::SUPERADMIN->value);
        } elseif ($user->isAdmin()) {
            // Admin sees themselves and all colaboradores
            $query->where(function ($q) use ($user) {
                $q->where('id', $user->id)
                    ->orWhere('role', \App\Enums\UserRole::COLLABORATOR->value);
            });
        } else {
            // Regular users see only themselves
            $query->where('id', $user->id);
        }

        $users = $query->get();

        return response()->json($users->map(function ($u) {

            $allocated = $u->vacationYears->sum('allocated_days');
            $used = $u->vacationYears->sum('used_days');

            return [
                'id' => $u->id,
                'name' => $u->name,
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'email' => $u->email,
                'identification' => $u->identification,
                'gender' => $u->gender,
                'phone' => $u->phone,
                'role' => $u->role instanceof \App\Enums\UserRole ? $u->role->value : $u->role,
                'is_active' => (bool) $u->is_active,
                'birth_date' => $u->birth_date?->toDateString(),
                'hire_date' => $u->hire_date?->toDateString(),
                'photo_path' => $u->photo_path,
                'photo_url' => $u->photo_url,
                'allocated' => $allocated,
                'used' => $used,
                'available' => $allocated - $used,
                'area_id' => $u->area_id,
                'area_name' => $u->area?->name,
            ];
        }));
    }

    public function adjust(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'days' => 'required|numeric|min:-365|max:365',
        ]);

        $year = now()->year;

        $vacation = $user->vacationYears()->firstOrCreate(
            ['year' => $year],
            [
                'tenant_id' => $user->tenant_id,
                'allocated_days' => 0,
                'used_days' => 0,
                'expires_at' => now()->endOfYear(),
            ]
        );

        if ($vacation->allocated_days + $data['days'] < $vacation->used_days) {
            return response()->json([
                'message' => 'El ajuste dejaría el año con menos días asignados que los ya disfrutados.',
                'errors' => ['days' => ['El ajuste dejaría el año con menos días asignados que los ya disfrutados.']],
            ], 422);
        }

        $vacation->increment('allocated_days', $data['days']);

        return response()->json([
            'message' => 'Días actualizados',
        ]);
    }
}
