<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // role permission admin
        $admin = Role::findByName('admin');
        $admin->givePermissionTo([
            // dashboard
            'dashboard.view',

            // user
            'user.view',
            'user.create',
            'user.update',
            'user.delete',

            // guru
            'teacher.view',
            'teacher.create',
            'teacher.update',
            'teacher.delete',

            // siswa
            'student.view',
            'student.create',
            'student.update',
            'student.delete',

            // kelas
            'class.view',
            'class.create',
            'class.update',
            'class.delete',

            // mata pelajaran
            'subject.view',
            'subject.create',
            'subject.update',
            'subject.delete',

            // tahun ajaran
            'academic-year.view',
            'academic-year.create',
            'academic-year.update',
            'academic-year.delete',

            // perangkat
            'device.view',
            'device.connect',
            'device.disconnect',

            // kehadiran
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',

            // laporan
            'report.view',
            'report.export',

            // pengaturan
            'setting.view',
            'setting.update',
        ]);

        // role permission kepala sekolah
        $headmaster = Role::findByName('headmaster');
        $headmaster->givePermissionTo([
            // dashboard
            'dashboard.view',

            // user
            'user.view',
            'user.create',
            'user.update',
            'user.delete',

            // guru
            'teacher.view',
            'teacher.create',
            'teacher.update',
            'teacher.delete',

            // siswa
            'student.view',
            'student.create',
            'student.update',
            'student.delete',

            // kelas
            'class.view',
            'class.create',
            'class.update',
            'class.delete',

            // mata pelajaran
            'subject.view',
            'subject.create',
            'subject.update',
            'subject.delete',

            // tahun ajaran
            'academic-year.view',
            'academic-year.create',
            'academic-year.update',
            'academic-year.delete',

            // perangkat
            'device.view',
            'device.connect',
            'device.disconnect',

            // kehadiran
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',

            // laporan
            'report.view',
            'report.export',

            // pengaturan
            'setting.view',
            'setting.update',
        ]);

        // role permission guru
        $teacher = Role::findByName('teacher');
        $teacher->givePermissionTo([
            // dashboard
            'dashboard.view',

            // siswa
            'student.view',

            // kehadiran
            'attendance.view',
            'attendance.create',
            'attendance.update',

            // laporan
            'report.view',
        ]);

        // role permission siswa
        $student = Role::findByName('student');
        $student->givePermissionTo([
            'dashboard.view',
            'attendance.view',
        ]);

        // role permission orang tua
        $parent = Role::findByName('parent');
        $parent->givePermissionTo([
            'dashboard.view',
            'attendance.view',
            'report.view',
        ]);
    }
}
