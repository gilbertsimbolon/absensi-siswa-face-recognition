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

    // relasi ke students
    public function students()
    {
        return $this->hasMany(Student::class, 'class_id');
    }

    // alias relasi ke student untuk backward compatibility
    public function student()
    {
        return $this->students();
    }

    // relasi ke riwayat kelas
    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class, 'class_id');
    }

    // relasi ke data absensi
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }
}
