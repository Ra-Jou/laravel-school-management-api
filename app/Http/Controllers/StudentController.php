<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:director,teacher')->except(['show']);
        $this->middleware('role:director,teacher,student')->only(['show']);
    }

    public function index()
    {
        return Student::with('user', 'schoolClass')->get();
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'matricule' => 'required|unique:students',
            'birth_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $student = Student::create($request->all());
        return response()->json($student->load('user'), 201);
    }

    public function show(Student $student)
    {
        return $student->load('user', 'schoolClass');
    }

    public function update(Request $request, Student $student)
    {
        $student->update($request->all());
        return response()->json($student->load('user'));
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return response()->json(null, 204);
    }
}
