<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Student;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth');
        parent::__construct();
    }

    /**
     * Display a listing of fees.
     * - Director/teacher: all fees
     * - Student: only their own fees
     */
    public function index()
    {
        if (in_array($this->currentUser->role, ['director', 'teacher'])) {
            return Fee::with('student.user')->get();
        }

        if ($this->currentUser->role === 'student') {
            if (! $this->currentUser->student) {
                abort(403, 'No student profile linked');
            }
            return $this->currentUser->student->fees()->with('student.user')->get();
        }

        abort(403, 'Access denied');
    }

    /**
     * Store a newly created fee.
     * Only for director.
     */
    public function store(Request $request)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'required|date',
            'status' => 'sometimes|in:pending,paid,failed',
        ]);

        $fee = Fee::create($validated);

        return response()->json($fee->load('student.user'), 201);
    }

    /**
     * Display the specified fee.
     * - Director/teacher: any fee
     * - Student: only their own
     */
    public function show(Fee $fee)
    {
        if (in_array($this->currentUser->role, ['director', 'teacher'])) {
            return $fee->load('student.user');
        }

        if ($this->currentUser->role === 'student') {
            if (! $this->currentUser->student || $this->currentUser->student->id !== $fee->student_id) {
                abort(403, 'Access denied');
            }
            return $fee->load('student.user');
        }

        abort(403, 'Access denied');
    }

    /**
     * Update the specified fee.
     * Only for director.
     */
    public function update(Request $request, Fee $fee)
    {
        $this->allowOnly(['director']);

        $validated = $request->validate([
            'student_id' => 'sometimes|exists:students,id',
            'amount' => 'sometimes|numeric|min:0',
            'due_date' => 'sometimes|date',
            'status' => 'sometimes|in:pending,paid,failed',
        ]);

        $fee->update($validated);

        return response()->json($fee->load('student.user'));
    }

    /**
     * Remove the specified fee.
     * Only for director.
     */
    public function destroy(Fee $fee)
    {
        $this->allowOnly(['director']);
        $fee->delete();
        return response()->json(null, 204);
    }
}
