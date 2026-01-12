<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function __construct() {}

    /**
     * Display a listing of the teachers.
     * Accès : director, teacher
     */
    public function index()
    {
        $user = Auth::user();

        if (! in_array($user->role, ['director', 'teacher'])) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        // Charger les users associés (sans mot de passe)
        return Teacher::with('user:id,name,email,role')->get();
    }

    /**
     * Store a newly created teacher (via user_id existant).
     * Seulement pour le director.
     *  A utiliser si tu crées d'abord le User manuellement.
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        if ($user->role !== 'director') {
            return response()->json(['error' => 'Seul le directeur peut créer un professeur'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'specialty' => 'nullable|string|max:255',
        ]);

        // Vérifier que le user_id correspond à un rôle "teacher" ou non encore assigné
        $targetUser = User::findOrFail($validated['user_id']);
        if ($targetUser->role !== 'teacher') {
            return response()->json(['error' => 'Le compte utilisateur doit avoir le rôle "teacher"'], 422);
        }

        $teacher = Teacher::create([
            'user_id' => $validated['user_id'],
            'specialty' => $validated['specialty'] ?? $targetUser->name,
        ]);

        return response()->json($teacher->load('user'), 201);
    }

    /**
     * Display the specified teacher.
     */
    public function show(Teacher $teacher)
    {
        $user = Auth::user();

        // Accès : director, teacher (tous), student (aucun accès)
        if (! in_array($user->role, ['director', 'teacher'])) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return $teacher->load('user:id,name,email,role');
    }

    /**
     * Update the specified teacher.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $user = Auth::user();

        if ($user->role !== 'director') {
            return response()->json(['error' => 'Seul le directeur peut modifier un professeur'], 403);
        }

        $validated = $request->validate([
            'specialty' => 'sometimes|nullable|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        $teacher->update($validated);

        return response()->json($teacher->load('user'));
    }

    /**
     * Remove the specified teacher.
     */
    public function destroy(Teacher $teacher)
    {
        $user = Auth::user();

        if ($user->role !== 'director') {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $teacher->delete();

        return response()->json(null, 204);
    }

    /**
     * Inscrire un nouveau professeur (User + Teacher en une seule requête)
     */
    public function registerTeacher(Request $request)
    {
        $currentUser = Auth::user();

        if ($currentUser->role !== 'director') {
            return response()->json(['error' => 'Seul le directeur peut inscrire un professeur'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'specialty' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated) {
            // 1. Créer le User
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'teacher',
            ]);

            // 2. Créer le profil Teacher
            $teacher = Teacher::create([
                'user_id' => $user->id,
                'specialty' => $validated['specialty'] ?? $validated['name'],
            ]);

            return response()->json([
                'message' => 'Professeur créé avec succès',
                'teacher' => $teacher->load('user')
            ], 201);
        });
    }
}
