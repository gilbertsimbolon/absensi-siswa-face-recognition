<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentClassHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'status',
    ];

    /**
     * Relasi ke siswa.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Relasi ke kelas.
     */
    public function classes()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    /**
     * Relasi ke tahun ajaran.
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
