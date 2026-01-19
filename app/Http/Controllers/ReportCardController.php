<?php

namespace App\Http\Controllers;

use App\Models\ReportCard;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of report cards.
     * - Director/teacher: all
     * - Student: only their own
     */
    public function index()
    {
        if (in_array($this->currentUser->role, ['director', 'teacher'])) {
            return ReportCard::with('student.user', 'subject')->get();
        }

        if ($this->currentUser->role === 'student') {
            if (! $this->currentUser->student) {
                abort(403, 'No student profile linked');
            }
            return $this->currentUser->student->reportCards()
                ->with('subject')
                ->get();
        }

        abort(403, 'Access denied');
    }

    /**
     * Store a newly created report card.
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'score' => 'required|numeric|min:0|max:20',
            'term' => 'required|string|in:1er trimestre,2e trimestre,3e trimestre',
            'academic_year' => 'required|integer|min:2000|max:2100',
        ]);

        // Empêcher les doublons manuellement (en plus de la DB)
        if (ReportCard::where('student_id', $validated['student_id'])
            ->where('subject_id', $validated['subject_id'])
            ->where('term', $validated['term'])
            ->where('academic_year', $validated['academic_year'])
            ->exists()
        ) {
            return response()->json([
                'error' => 'Bulletin already exists for this student, subject, term and year'
            ], 422);
        }

        $reportCard = ReportCard::create($validated);

        return response()->json($reportCard->load('student.user', 'subject'), 201);
    }

    /**
     * Display the specified report card.
     * - Director/teacher: any
     * - Student: only their own
     */
    public function show(ReportCard $reportCard)
    {
        if (in_array($this->currentUser->role, ['director', 'teacher'])) {
            return $reportCard->load('student.user', 'subject');
        }

        if ($this->currentUser->role === 'student') {
            if (! $this->currentUser->student || $this->currentUser->student->id !== $reportCard->student_id) {
                abort(403, 'Access denied');
            }
            return $reportCard->load('subject');
        }

        abort(403, 'Access denied');
    }

    /**
     * Update the specified report card.
     * Only for director.
     */
    public function update(Request $request, ReportCard $reportCard)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
            'subject_id' => 'sometimes|exists:subjects,id',
            'score' => 'sometimes|numeric|min:0|max:20',
            'term' => 'sometimes|string|in:1er trimestre,2e trimestre,3e trimestre',
            'academic_year' => 'sometimes|integer|min:2000|max:2100',
        ]);

        $reportCard->update($validated);

        return response()->json($reportCard->load('student.user', 'subject'));
    }

    /**
     * Remove the specified report card.
     * Only for director.
     */
    public function destroy(ReportCard $reportCard)
    {
        $this->allowOnly(['director']);
        $reportCard->delete();
        return response()->json(null, 204);
    }
}
