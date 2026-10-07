<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public const STATUS_HADIR = 'hadir';

    public const STATUS_TERLAMBAT = 'terlambat';

    public const STATUS_SAKIT = 'sakit';

    public const STATUS_IZIN = 'izin';

    public const STATUS_ALPA = 'alpa';

    public const METHOD_FACE = 'face_recognition';

    public const METHOD_MANUAL = 'manual';

    protected $fillable = [
        'student_id',
        'class_id',
        'academic_year_id',
        'date',
        'check_in_time',
        'check_out_time',
        'status',
        'method',
        'confidence_score',
        'snapshot_path',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'confidence_score' => 'float',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function classes()
    {
        return $this->belongsTo(Classes::class, 'class_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_HADIR => 'Hadir',
            self::STATUS_TERLAMBAT => 'Terlambat',
            self::STATUS_SAKIT => 'Sakit',
            self::STATUS_IZIN => 'Izin',
            self::STATUS_ALPA => 'Alpa',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_HADIR => 'bg-label-success',
            self::STATUS_TERLAMBAT => 'bg-label-warning',
            self::STATUS_SAKIT => 'bg-label-info',
            self::STATUS_IZIN => 'bg-label-primary',
            self::STATUS_ALPA => 'bg-label-danger',
            default => 'bg-label-secondary',
        };
    }
}
