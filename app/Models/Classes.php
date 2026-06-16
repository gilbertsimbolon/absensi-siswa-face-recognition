<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'name', 
        'grade_level',
    ];

    // relasi ke teacher
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // relasi ke student
    public function student()
    {
        return $this->hasMany(Student::class);
    }
}
