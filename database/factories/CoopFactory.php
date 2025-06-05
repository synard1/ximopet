<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Coop;

class CoopFactory extends Factory
{
    protected $model = Coop::class;

    public function definition()
    {
        return [
            'code' => 'C' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => $this->faker->company . '-Kandang',
            'capacity' => 100000, // Adjust as needed
            'status' => 'active',
            'created_by' => 3,
        ];
    }
}
