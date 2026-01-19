<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;          // ← Ajouté
use App\Models\SchoolClass;   // ← Ajouté (optionnel mais recommandé)
use App\Models\Fee;           // ← Ajouté (optionnel)
use App\Models\ReportCard;    // ← Ajouté (optionnel)

class Student extends Model
{
    /** @use HasFactory<\Database\Factories\StudentFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'matricule',
        'birth_date',
        'phone',
        'class_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function reportCards()
    {
        return $this->hasMany(ReportCard::class);
    }
}
