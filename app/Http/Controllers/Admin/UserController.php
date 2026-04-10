<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(
            User::select(
                'id',
                'name',
                'first_name',
                'last_name',
                'identification',
                'phone',
                'email',
                'role',
                'birth_date',
                'hire_date',
                'photo_path',
                'area_id'
            )->with('area')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'identification' => ['nullable', 'string', 'max:50', Rule::unique('users', 'identification')],
            'phone' => 'nullable|string|max:30',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,colaborador',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $data['name'] = trim($data['first_name'].' '.$data['last_name']);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        unset($data['photo']);

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        $user->load(['vacationYears', 'area']);

        $allocated = $user->vacationYears->sum('allocated_days');
        $used = $user->vacationYears->sum('used_days');

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'identification' => $user->identification,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'birth_date' => $user->birth_date?->toDateString(),
            'hire_date' => $user->hire_date?->toDateString(),
            'photo_path' => $user->photo_path,
            'photo_url' => $user->photo_url,
            'allocated' => $allocated,
            'used' => $used,
            'available' => $allocated - $used,
            'area_id' => $user->area_id,
            'area_name' => $user->area?->name,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'identification' => ['nullable', 'string', 'max:50', Rule::unique('users', 'identification')->ignore($user->id)],
            'phone' => 'nullable|string|max:30',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'role' => 'sometimes|in:admin,colaborador',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048',
            'area_id' => 'nullable|exists:areas,id',
        ]);

        $firstName = $data['first_name'] ?? $user->first_name;
        $lastName = $data['last_name'] ?? $user->last_name;
        $data['name'] = trim($firstName.' '.$lastName);

        if ($request->hasFile('photo')) {
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        unset($data['photo']);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(User $user)
    {
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}
