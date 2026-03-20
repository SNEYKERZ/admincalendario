<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', User::class);

        $users = User::with('vacationYears')->get();

        return response()->json($users->map(function ($u) {

            $allocated = $u->vacationYears->sum('allocated_days');
            $used = $u->vacationYears->sum('used_days');

            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
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
            'days' => 'required|numeric'
        ]);

        $year = now()->year;

        $vacation = $user->vacationYears()->firstOrCreate(
            ['year' => $year],
            [
                'allocated_days' => 0,
                'used_days' => 0,
                'expires_at' => now()->endOfYear()
            ]
        );

        $vacation->increment('allocated_days', $data['days']);

        return response()->json([
            'message' => 'Días actualizados'
        ]);
    }
}