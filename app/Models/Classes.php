<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classes extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'grade_level',
    ];

    // relasi ke teacher
    public function teacher()
    {
        return $this->hasOne(Teacher::class, 'id', 'teacher_id');
    }

    // relasi ke student
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
