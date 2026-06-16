<?php

namespace App\Models;

use App\Models\Classes;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nisn',
        'class_id',
        'gender',
    ];

    // relasi ke user
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    // relasi ke kelas
    public function class()
    {
        return $this->hasMany(Classes::class, 'id', 'class_id');
    }
}
