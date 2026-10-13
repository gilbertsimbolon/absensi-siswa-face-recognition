<?php

namespace Database\Factories;

use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nisn' => fake()->unique()->numerify('00########'),
            'class_id' => null,
            'gender' => fake()->randomElement(['L', 'P']),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
            'parent_name' => fake()->name(),
            'parent_phone' => fake()->phoneNumber(),
            'parent_email' => fake()->safeEmail(),
            'status' => 'aktif',
        ];
    }
}
