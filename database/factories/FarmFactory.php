<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Farm;

class FarmFactory extends Factory
{
    protected $model = Farm::class;

    public function definition()
    {
        return [
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{3}'),
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'phone_number' => $this->faker->phoneNumber,
            'contact_person' => $this->faker->name,
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'email' => $this->faker->email,
            'notes' => $this->faker->sentence,
        ];
    }
}
