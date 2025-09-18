<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class  UserFactory extends Factory
{
    public function definition()
    {
        $role = $this->faker->randomElement(['student', 'instructor']);

        $firstName = $this->faker->firstName;
        $lastName = $this->faker->lastName;

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'user_name' => Str::slug($firstName . $lastName) . rand(1, 99),
            'email' => $this->faker->unique()->safeEmail,
            'password' => Hash::make('password'),
            'avatar' => $this->faker->imageUrl(200, 200, 'people'),
            'role' => $role,
        ];
    }
}
