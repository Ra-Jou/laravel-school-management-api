<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of students.
     * Access: director, teacher
     */
    public function index()
    {
        $this->allowOnly(['director', 'teacher']);
        return Student::with('user', 'schoolClass')->get();
    }

    /**
     * Display the specified student.
     * - Students can only view their own profile.
     * - Directors and teachers can view any student.
     */
    public function show(Student $student)
    {
        if (in_array($this->currentUser->role, ['director', 'teacher'])) {
            return $student->load('user', 'schoolClass', 'fees', 'reportCards');
        }

        if ($this->currentUser->role === 'student') {
            if (! $this->currentUser->student || $this->currentUser->student->id !== $student->id) {
                abort(403, 'Access denied');
            }
            return $student->load('user', 'schoolClass', 'fees', 'reportCards');
        }

        abort(403, 'Unauthorized role');
    }

    /**
     * Store a new student (requires existing user_id).
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'matricule' => 'required|unique:students',
            'birth_date' => 'required|date',
            'class_id' => 'nullable|exists:school_classes,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $student = Student::create($validated);
        return response()->json($student->load('user'), 201);
    }

    /**
     * Update the specified student.
     * Only for director.
     */
    public function update(Request $request, Student $student)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'matricule' => 'sometimes|required|unique:students,matricule,' . $student->id,
            'birth_date' => 'sometimes|required|date',
            'class_id' => 'sometimes|nullable|exists:school_classes,id',
            'user_id' => 'sometimes|exists:users,id',
            'phone' => 'sometimes|nullable|string|max:20',
        ]);

        $student->update($validated);
        return response()->json($student->load('user', 'schoolClass'));
    }

    /**
     * Remove the specified student.
     * Only for director.
     */
    public function destroy(Student $student)
    {
        $this->allowOnly(['director']);
        $student->delete();
        return response()->json(null, 204);
    }

    /**
     * Register a new student by creating both User and Student records.
     * Only for director.
     */
    public function registerStudent(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'birth_date' => 'required|date',
            'class_id' => 'nullable|exists:school_classes,id',
            'phone' => 'nullable|string|max:20',
        ]);

        $latest = Student::orderBy('id', 'desc')->first();
        $nextId = $latest ? (int) substr($latest->matricule, 3) + 1 : 1;
        $matricule = 'STU' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

        $userRecord = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'student',
        ]);

        $student = Student::create([
            'user_id' => $userRecord->id,
            'matricule' => $matricule,
            'birth_date' => $validated['birth_date'],
            'class_id' => $validated['class_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        return response()->json([
            'message' => 'Student registered successfully',
            'student' => $student->load('user', 'schoolClass'),
        ], 201);
    }
}
