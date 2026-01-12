<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of subjects.
     * Access: director, teacher
     */
    public function index()
    {
        $this->allowOnly(['director', 'teacher']);
        return Subject::all();
    }

    /**
     * Store a newly created subject.
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:20|unique:subjects,code',
        ]);

        $subject = Subject::create($validated);

        return response()->json($subject, 201);
    }

    /**
     * Display the specified subject.
     * Access: director, teacher
     */
    public function show(Subject $subject)
    {
        $this->allowOnly(['director', 'teacher']);
        return $subject;
    }

    /**
     * Update the specified subject.
     * Only for director.
     */
    public function update(Request $request, Subject $subject)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|max:20|unique:subjects,code,' . $subject->id,
        ]);

        $subject->update($validated);

        return response()->json($subject);
    }

    /**
     * Remove the specified subject.
     * Only for director.
     */
    public function destroy(Subject $subject)
    {
        $this->allowOnly(['director']);

        // Optionnel : empêcher la suppression si des bulletins existent
        if ($subject->reportCards()->exists()) {
            return response()->json([
                'error' => 'Cannot delete subject with existing report cards'
            ], 422);
        }

        $subject->delete();

        return response()->json(null, 204);
    }
}
