<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Teacher extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nip',
        'phone',
    ];

    // relasi ke classes
    public function classes()
    {
        return $this->belongsTo(Classes::class);
    }
}
