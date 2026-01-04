<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Subject;
use App\Models\Fee;
use App\Models\ReportCard;

class SchoolSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Créer les classes
        $classes = [
            SchoolClass::create(['name' => '6ème A', 'level' => 1]),
            SchoolClass::create(['name' => '4ème B', 'level' => 2]),
            SchoolClass::create(['name' => 'Terminale C', 'level' => 3]),
        ];

        // 2. Créer les utilisateurs
        $director = User::create([
            'name' => 'Directeur École',
            'email' => 'director@school.com',
            'password' => bcrypt('password123'),
            'role' => 'director'
        ]);

        $teachers = [
            User::create([
                'name' => 'Prof. Mathématiques',
                'email' => 'maths@school.com',
                'password' => bcrypt('password123'),
                'role' => 'teacher'
            ]),
            User::create([
                'name' => 'Prof. Histoire',
                'email' => 'histoire@school.com',
                'password' => bcrypt('password123'),
                'role' => 'teacher'
            ]),
        ];

        $studentsUsers = [];
        foreach (
            [
                ['Jean Dupont', 'jean@student.com'],
                ['Marie Lemoine', 'marie@student.com'],
                ['Lucas Martin', 'lucas@student.com'],
                ['Chloé Dubois', 'chloe@student.com'],
                ['Tom Renard', 'tom@student.com'],
            ] as $i => $data
        ) {
            $studentsUsers[] = User::create([
                'name' => $data[0],
                'email' => $data[1],
                'password' => bcrypt('password123'),
                'role' => 'student'
            ]);
        }

        // 3. Lier professeurs
        foreach ($teachers as $teacherUser) {
            Teacher::create([
                'user_id' => $teacherUser->id,
                'specialty' => $teacherUser->name
            ]);
        }

        // 4. Lier élèves à des classes
        $students = [];
        foreach ($studentsUsers as $index => $studentUser) {
            $class = $classes[$index % count($classes)];
            $students[] = Student::create([
                'user_id' => $studentUser->id,
                'matricule' => 'STU' . str_pad($index + 1, 3, '0', STR_PAD_LEFT),
                'birth_date' => now()->subYears(12 + $index)->format('Y-m-d'),
                'class_id' => $class->id
            ]);
        }

        // 5. Créer les matières
        $subjects = [
            Subject::create(['name' => 'Mathématiques', 'code' => 'MATH']),
            Subject::create(['name' => 'Histoire-Géo', 'code' => 'HG']),
            Subject::create(['name' => 'Physique-Chimie', 'code' => 'PC']),
            Subject::create(['name' => 'Français', 'code' => 'FR']),
        ];

        // 6. Créer des frais
        foreach ($students as $student) {
            Fee::create([
                'student_id' => $student->id,
                'amount' => 15000.00,
                'status' => 'pending',
                'due_date' => now()->addDays(15)->format('Y-m-d')
            ]);
        }

        // 7. Créer des bulletins (notes)
        foreach ($students as $student) {
            foreach ($subjects as $subject) {
                ReportCard::create([
                    'student_id' => $student->id,
                    'subject_id' => $subject->id,
                    'score' => rand(8, 18) + rand(0, 9) / 10,
                    'term' => '1er trimestre',
                    'academic_year' => 2026
                ]);
            }
        }
    }
}
