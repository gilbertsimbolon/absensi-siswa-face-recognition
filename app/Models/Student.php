<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'nisn',
        'class_id',
        'gender',
        'phone',
        'parent_name',
        'parent_phone',
    ];

    // relasi ke user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relasi ke kelas
    public function classes()
    {
        return $this->belongsTo(Classes::class);
    }

    // relasi ke data wajah siswa
    public function faces()
    {
        return $this->hasMany(StudentFace::class);
    }
}
