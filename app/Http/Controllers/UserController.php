<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of users.
     * - Director: sees all
     * - Teacher: sees all except directors
     */
    public function index()
    {
        $this->allowOnly(['director', 'teacher']);

        $query = User::select('id', 'name', 'email', 'role', 'created_at');

        if ($this->currentUser->role === 'teacher') {
            $query->where('role', '!=', 'director');
        }

        return $query->get();
    }

    /**
     * Create a new user.
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

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
     * - Users can view their own profile.
     * - Director/teacher can view others.
     */
    public function show(User $user)
    {
        if ($this->currentUser->id === $user->id || in_array($this->currentUser->role, ['director', 'teacher'])) {
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'created_at' => $user->created_at,
            ]);
        }

        abort(403, 'Access denied');
    }

    /**
     * Update the specified user.
     * Only for director.
     */
    public function update(Request $request, User $user)
    {
        $this->allowOnly(['director']);

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

        $user->update($data);

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * Remove the specified user.
     * Only for director.
     * Prevent self-deletion.
     */
    public function destroy(User $user)
    {
        $this->allowOnly(['director']);

        if ($this->currentUser->id === $user->id) {
            abort(403, 'Cannot delete your own account');
        }

        $user->delete();
        return response()->json(null, 204);
    }
}
