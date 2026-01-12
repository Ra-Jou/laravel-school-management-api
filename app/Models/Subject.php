<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code'];

    // Relations (optionnelles pour l'instant)
    public function reportCards()
    {
        return $this->hasMany(ReportCard::class);
    }
}
