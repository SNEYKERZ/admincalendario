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
        $query = User::with('vacationYears');

        if ($user->isSuperAdmin()) {
            // Superadmin sees everyone except themselves (handled by UI)
            $query->where('role', '!=', \App\Enums\UserRole::SUPERADMIN->value);
        } elseif ($user->isAdmin()) {
            // Admin sees only colaboradores
            $query->where('role', \App\Enums\UserRole::COLLABORATOR->value);
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
                'phone' => $u->phone,
                'role' => $u->role instanceof \App\Enums\UserRole ? $u->role->value : $u->role,
                'birth_date' => $u->birth_date?->toDateString(),
                'hire_date' => $u->hire_date?->toDateString(),
                'photo_path' => $u->photo_path,
                'photo_url' => $u->photo_url,
                'allocated' => $allocated,
                'used' => $used,
                'available' => $allocated - $used,
            ];
        }));
    }

    public function adjust(Request $request, User $user)
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'days' => 'required|numeric',
        ]);

        $year = now()->year;

        $vacation = $user->vacationYears()->firstOrCreate(
            ['year' => $year],
            [
                'allocated_days' => 0,
                'used_days' => 0,
                'expires_at' => now()->endOfYear(),
            ]
        );

        $vacation->increment('allocated_days', $data['days']);

        return response()->json([
            'message' => 'Días actualizados',
        ]);
    }
}
