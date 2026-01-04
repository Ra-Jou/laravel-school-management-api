<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api');
    }

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
        if ($user->role === 'student') {
            if ($user->student->id !== $student->id) {
                return response()->json(['error' => 'Accès refusé'], 403);
            }
        }

        return $student->load('user', 'schoolClass', 'fees', 'reportCards.subject');
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

        $student->update($request->all());
        return response()->json($student->load('user'));
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
