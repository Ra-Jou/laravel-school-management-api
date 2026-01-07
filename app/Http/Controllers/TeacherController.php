<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TeacherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        // Seulement directeur et professeur peuvent lister tous
        // if (! in_array($user->role, ['director', 'teacher'])) {
        //     return response()->json(['error' => 'Accès refusé'], 403);
        // }
        // return Teacher::with('user')->get();
        return "false";
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd(Teacher::all());
    }

    /**
     * Display the specified resource.
     */
    public function show(Teacher $teacher)
    {
        // dd(Teacher::find($teacher->id));
        $user = Auth::user();

        if (in_array($user->role, ['director', 'teacher'])) {
            return "true";
            // return $teacher->load('user', 'specialty');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
