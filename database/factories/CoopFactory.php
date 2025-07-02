<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Coop;
use App\Models\Farm;
use App\Models\User;

class CoopFactory extends Factory
{
    protected $model = Coop::class;

    public function definition()
    {
        return [
            'farm_id' => Farm::factory(),
            'code' => $this->faker->unique()->regexify('[A-Z]{2}[0-9]{3}'),
            'name' => $this->faker->words(2, true),
            'capacity' => $this->faker->numberBetween(1000, 10000),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'notes' => $this->faker->sentence,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
