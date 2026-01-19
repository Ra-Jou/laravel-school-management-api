<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'level_group', 'level_order'];

    // Accesseur pour trier facilement
    public function getLevelGroupLabelAttribute()
    {
        return match ($this->level_group) {
            'maternelle' => 'Maternelle',
            'primaire' => 'Primaire',
            'college' => 'Collège',
            'lycee' => 'Lycée',
            default => ucfirst($this->level_group),
        };
    }

    // Relation avec les élèves
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}
