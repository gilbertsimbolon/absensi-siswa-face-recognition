<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
            'guru.view',
            'guru.create',
            'guru.update',
            'guru.delete',

            // permission data siswa
            'siswa.view',
            'siswa.create',
            'siswa.update',
            'siswa.update',

            // permission data kelas
            
        ];
    }
}
