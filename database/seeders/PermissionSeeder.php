<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // permission dashboard
            'dashboard.view',

            // permission data user
            'user.view',
            'user.create',
            'user.update',
            'user.delete',

            // permission data guru
            'teacher.view',
            'teacher.create',
            'teacher.update',
            'teacher.delete',

            // permission data siswa
            'student.view',
            'student.create',
            'student.update',
            'student.delete',

            // permission data kelas
            'class.view',
            'class.create',
            'class.update',
            'class.delete',

            // permission data mata pelajaran
            'subject.view',
            'subject.create',
            'subject.update',
            'subject.delete',

            // permission data tahun ajaran
            'academic-year.view',
            'academic-year.create',
            'academic-year.update',
            'academic-year.delete',

            // permission kelola perangkat
            'device.view',
            'device.connect',
            'device.disconnect',

            // permission data kehadiran
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',

            // permission laporan
            'report.view',
            'report.export',

            // permission pengaturan
            'setting.view',
            'setting.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }
}
