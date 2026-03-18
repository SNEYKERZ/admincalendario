<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTAR USUARIOS
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        return response()->json(
            User::select('id','name','email','role','photo_path')->get()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREAR USUARIO
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,colaborador',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048'
        ]);

        // manejar imagen
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        $data['password'] = Hash::make($data['password']);

        $user = User::create($data);

        return response()->json($user, 201);
    }

    /*
    |--------------------------------------------------------------------------
    | ACTUALIZAR USUARIO
    |--------------------------------------------------------------------------
    */

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:admin,colaborador',
            'birth_date' => 'nullable|date',
            'hire_date' => 'nullable|date',
            'photo' => 'nullable|image|max:2048'
        ]);

        if ($request->hasFile('photo')) {

            // eliminar anterior
            if ($user->photo_path) {
                Storage::disk('public')->delete($user->photo_path);
            }

            $data['photo_path'] = $request->file('photo')->store('users', 'public');
        }

        $user->update($data);

        return response()->json($user);
    }

    /*
    |--------------------------------------------------------------------------
    | ELIMINAR USUARIO
    |--------------------------------------------------------------------------
    */

    public function destroy(User $user)
    {
        if ($user->photo_path) {
            Storage::disk('public')->delete($user->photo_path);
        }

        $user->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }
}