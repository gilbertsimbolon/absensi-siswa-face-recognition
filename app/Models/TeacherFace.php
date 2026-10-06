<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherFace extends Model
{
    protected $fillable = [
        'teacher_id',
        'file_path',
        'label',
    ];

    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }
}
