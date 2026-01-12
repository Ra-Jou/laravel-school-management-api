<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller
{
    public function __construct() {}

    /**
     * Display a listing of users.
     * Acces: director, teacher
     */
    public function index()
    {
        $user = Auth::user();

        if (! in_array($user->role, ['director', 'teacher'])) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Option : Hide lpassword and tokken
        return User::select('id', 'name', 'email', 'role', 'created_at')
            ->when($user->role === 'teacher', function ($query) {
                return $query->where('role', '!=', 'director');
            })
            ->get();
    }

    /**
     * Create a new user 
     * Only the Director
     */
    public function store(Request $request)
    {
        $currentUser = Auth::user();

        if ($currentUser->role !== 'director') {
            return response()->json(['error' => 'Seul le directeur peut créer un utilisateur'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:director,teacher,student',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'created_at' => $user->created_at,
        ], 201);
    }

    /**
     * Display the specified user.
     * Access: 
     *  - all user can see his profile
     *  - Director/teacher can see ohter profile
     */
    public function show(User $user)
    {
        $currentUser = Auth::user();

        // Access for each other
        if ($currentUser->id === $user->id) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]);
        }

        // Access for director/teacher
        if (in_array($currentUser->role, ['director', 'teacher'])) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]);
        }

        return response()->json(['error' => 'Accès refusé'], 403);
    }

    /**
     * Update user
     */
    public function update(Request $request, User $user)
    {
        $currentUser = Auth::user();

        if ($currentUser->role !== 'director') {
            return response()->json(['error' => 'Seul le directeur peut modifier un utilisateur'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|string|min:8',
            'role' => 'sometimes|required|in:director,teacher,student',
        ]);

        $data = [
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
            'role' => $validated['role'] ?? $user->role,
        ];

        if (isset($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Remove user.
     */
    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        if ($currentUser->role !== 'director') {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        if ($currentUser->id === $user->id) {
            return response()->json(['error' => 'Vous ne pouvez pas supprimer votre propre compte'], 403);
        }

        $user->delete();

        return response()->json(null, 204);
    }
}
