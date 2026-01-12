<?php

namespace App\Http\Controllers;

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
}
