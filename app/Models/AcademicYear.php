<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'semester',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke riwayat kelas siswa.
     */
    public function classHistories()
    {
        return $this->hasMany(StudentClassHistory::class);
    }

    /**
     * Relasi ke data absensi siswa.
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Dapatkan tahun ajaran yang sedang aktif.
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }
}
