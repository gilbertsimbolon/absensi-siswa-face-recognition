<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'admin',
            'headmaster',
            'teacher',
            'student',
            'parent',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate([
                'nama' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
