<?php

namespace App\Http\Controllers;

use App\Models\ReportCard;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class SchoolClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of school classes, ordered by level.
     */
    public function index()
    {
        $this->allowOnly(['director', 'teacher']);

        return SchoolClass::orderBy('level_group')
            ->orderBy('level_order')
            ->get()
            ->map(function ($class) {
                $class->level_group_label = $class->level_group_label;
                return $class;
            });
    }

    /**
     * Store a newly created school class.
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'level_group' => 'required|in:maternelle,primaire,college,lycee',
            'level_order' => 'required|integer|min:1',
        ]);

        $schoolClass = SchoolClass::create($validated);

        return response()->json($schoolClass, 201);
    }

    /**
     * Display the specified school class.
     */
    public function show(SchoolClass $schoolClass)
    {
        $this->allowOnly(['director', 'teacher']);
        $schoolClass->level_group_label = $schoolClass->level_group_label;
        return $schoolClass;
    }

    /**
     * Update the specified school class.
     * Only for director.
     */
    public function update(Request $request, SchoolClass $schoolClass)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'level_group' => 'sometimes|required|in:maternelle,primaire,college,lycee',
            'level_order' => 'sometimes|required|integer|min:1',
        ]);

        $schoolClass->update($validated);
        $schoolClass->level_group_label = $schoolClass->level_group_label;

        return response()->json($schoolClass);
    }

    /**
     * Remove the specified school class.
     * Only for director.
     */
    public function destroy(SchoolClass $schoolClass)
    {
        $this->allowOnly(['director']);

        if ($schoolClass->students()->exists()) {
            return response()->json([
                'error' => 'Cannot delete class with enrolled students'
            ], 422);
        }

        $schoolClass->delete();
        return response()->json(null, 204);
    }

    /**
     * Get the full report card for all students in a class by term and year.
     */
    public function getClassReportCard(SchoolClass $schoolClass, Request $request)
    {
        $this->allowOnly(['director', 'teacher']);

        $validated = $request->validate([
            'term' => 'required|string|in:1er trimestre,2e trimestre,3e trimestre',
            'year' => 'required|integer|min:2000|max:2100',
        ]);

        // Charger tous les élèves de la classe
        $students = $schoolClass->students()
            ->with('user')
            ->get();

        if ($students->isEmpty()) {
            return response()->json([
                'message' => 'No students found in this class'
            ], 404);
        }

        // Récupérer tous les bulletins de la classe pour ce trimestre/année
        $reportCards = ReportCard::with('subject')
            ->whereIn('student_id', $students->pluck('id'))
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['year'])
            ->get();

        // Grouper les notes par élève
        $studentNotes = $reportCards->groupBy('student_id');

        $result = $students->map(function ($student) use ($studentNotes, $validated) {
            $notes = $studentNotes->get($student->id, collect());

            if ($notes->isEmpty()) {
                return [
                    'student_id' => $student->id,
                    'name' => $student->user?->name ?? 'N/A',
                    'matricule' => $student->matricule,
                    'subjects' => [],
                    'average' => null
                ];
            }

            $subjects = $notes->map(function ($note) {
                return [
                    'subject' => $note->subject?->name ?? 'Unknown',
                    'score' => (float) $note->score,
                    'comment' => $note->comment ?? null,
                ];
            });

            $average = round($notes->sum('score') / $notes->count(), 2);

            return [
                'student_id' => $student->id,
                'name' => $student->user?->name ?? 'N/A',
                'matricule' => $student->matricule,
                'subjects' => $subjects,
                'average' => $average
            ];
        });

        return response()->json([
            'class_name' => $schoolClass->name,
            'term' => $validated['term'],
            'academic_year' => $validated['year'],
            'students' => $result
        ]);
    }
}
