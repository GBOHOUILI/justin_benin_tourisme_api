<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class UserController extends Controller
{
    public function index()
    {
        // Retourne sans relations pour éviter la récursion
        return response()->json(User::paginate(20));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom'                  => 'required|string|max:100',
            'prenom'               => 'required|string|max:100',
            'tel'                  => 'required|string|max:20',
            'email'                => 'required|email|unique:users,email',
            'password'             => 'required|string|min:8|confirmed',
            'nationalite'          => 'nullable|string|max:100',
            'longitude'            => 'nullable|numeric',
            'latitude'             => 'nullable|numeric',
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        return response()->json($user, 201);
    }

    public function show(User $user)
    {
        // CORRECTION : on ne charge plus reservations.tickets (récursion infinie)
        // On retourne juste le user sans relations lourdes
        return response()->json($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'nom'         => 'sometimes|string|max:100',
            'prenom'      => 'sometimes|string|max:100',
            'tel'         => 'sometimes|string|max:20',
            'email'       => 'sometimes|email|unique:users,email,' . $user->id,
            'password'    => 'sometimes|string|min:8|confirmed',
            'nationalite' => 'nullable|string|max:100',
            'longitude'   => 'nullable|numeric',
            'latitude'    => 'nullable|numeric',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);
        return response()->json($user);
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé'], 200);
    }
}