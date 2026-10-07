<?php

namespace Database\Factories;

use App\Models\Classes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classes>
 */
class ClassesFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Kelas '.fake()->unique()->numerify('###'),
            'grade_level' => fake()->randomElement(['X', 'XI', 'XII']),
            'teacher_id' => null,
        ];
    }
}
