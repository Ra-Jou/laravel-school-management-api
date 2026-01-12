<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class TeacherController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of teachers.
     * Access: director, teacher
     */
    public function index()
    {
        $this->allowOnly(['director', 'teacher']);
        return Teacher::with('user:id,name,email,role')->get();
    }

    /**
     * Store a new teacher (requires existing user_id with role=teacher).
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'specialty' => 'nullable|string|max:255',
        ]);

        $targetUser = User::findOrFail($validated['user_id']);
        if ($targetUser->role !== 'teacher') {
            abort(422, 'User must have role "teacher"');
        }

        $teacher = Teacher::create([
            'user_id' => $validated['user_id'],
            'specialty' => $validated['specialty'] ?? $targetUser->name,
        ]);

        return response()->json($teacher->load('user'), 201);
    }

    /**
     * Display the specified teacher.
     * Access: director, teacher
     */
    public function show(Teacher $teacher)
    {
        $this->allowOnly(['director', 'teacher']);
        return $teacher->load('user:id,name,email,role');
    }

    /**
     * Update the specified teacher.
     * Only for director.
     */
    public function update(Request $request, Teacher $teacher)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'specialty' => 'sometimes|nullable|string|max:255',
            'user_id' => 'sometimes|exists:users,id',
        ]);

        $teacher->update($validated);
        return response()->json($teacher->load('user'));
    }

    /**
     * Remove the specified teacher.
     * Only for director.
     */
    public function destroy(Teacher $teacher)
    {
        $this->allowOnly(['director']);
        $teacher->delete();
        return response()->json(null, 204);
    }

    /**
     * Register a new teacher by creating both User and Teacher records.
     * Only for director.
     */
    public function registerTeacher(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'specialty' => 'nullable|string|max:255',
        ]);

        return DB::transaction(function () use ($validated) {
            $userRecord = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'teacher',
            ]);

            $teacher = Teacher::create([
                'user_id' => $userRecord->id,
                'specialty' => $validated['specialty'] ?? $validated['name'],
            ]);

            return response()->json([
                'message' => 'Teacher registered successfully',
                'teacher' => $teacher->load('user'),
            ], 201);
        });
    }
}
