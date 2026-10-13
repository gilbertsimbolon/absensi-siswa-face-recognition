<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_face_accuracy',
    ];

    protected $casts = [
        'min_face_accuracy' => 'float',
    ];

    /**
     * Dapatkan instance pengaturan aplikasi (singleton pattern).
     */
    public static function getSettings(): self
    {
        $setting = self::first();

        if (! $setting) {
            $setting = self::create([
                'min_face_accuracy' => 70.00,
            ]);
        }

        return $setting;
    }

    /**
     * Dapatkan nilai batas minimum akurasi pemindaian wajah.
     */
    public static function getMinFaceAccuracy(): float
    {
        return (float) self::getSettings()->min_face_accuracy;
    }
}
