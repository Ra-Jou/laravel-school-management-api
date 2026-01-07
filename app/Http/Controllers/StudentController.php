<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function __construct() {}

    public function index()
    {
        $user = Auth::user();

        // Seulement directeur et professeur peuvent lister tous
        if (! in_array($user->role, ['director', 'teacher'])) {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        return Student::with('user', 'schoolClass')->get();
    }

    public function show(Student $student)
    {
        $user = Auth::user();


        // Un élève ne peut voir que son propre dossier
        if (in_array($user->role, ['director', 'teacher'])) {
            return $student->load('user', 'schoolClass', 'fees', 'reportCards');
        }

        if ($user->role === 'student') {
            if (!$user->student) {
                return response()->json(['error' => "Votre compte n'es pas associé a ce profile étudiant"], 403);
            }

            if ($user->student->id !== $student->id) {
                return response()->json(['error' => 'Accès refusé'], 403);
            }

            return $student->load('user', 'schoolClass', 'fees', 'reportCards');
        }

        return response()->json(['error' => 'Role non autorise'], 403);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! in_array($user->role, ['director'])) {
            return response()->json(['error' => 'Seul le directeur peut créer un élève'], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'matricule' => 'required|unique:students',
            'birth_date' => 'required|date',
            'class_id' => 'nullable|exists:school_classes,id'
        ]);

        $student = Student::create($request->all());
        return response()->json($student->load('user'), 201);
    }

    public function update(Request $request, Student $student)
    {
        $user = Auth::user();

        if (! in_array($user->role, ['director'])) {
            return response()->json(['error' => 'Seul le directeur peut modifier'], 403);
        }

        //  Validation explicite
        $validated = $request->validate([
            'matricule' => 'sometimes|required|unique:students,matricule,' . $student->id,
            'birth_date' => 'sometimes|required|date',
            'class_id' => 'sometimes|nullable|exists:school_classes,id',
            'user_id' => 'sometimes|exists:users,id',
            'phone' => 'sometimes|nullable|string|max:20'
        ]);


        // dd($validated);
        $student->update($validated);

        return response()->json($student->load('user', 'schoolClass'));
    }

    public function destroy(Student $student)
    {
        $user = Auth::user();
        if ($user->role !== 'director') {
            return response()->json(['error' => 'Accès refusé'], 403);
        }

        $student->delete();
        return response()->json(null, 204);
    }
}
