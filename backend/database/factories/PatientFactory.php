<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName('female'),
            'last_name' => fake()->lastName(),
            'sex' => 'female',
            'date_of_birth' => fake()->dateTimeBetween('-45 years', '-16 years')->format('Y-m-d'),
            'dob_estimated' => false,
            'national_id' => fake()->unique()->bothify('??######'),
            'address' => fake()->address(),
        ];
    }
}
