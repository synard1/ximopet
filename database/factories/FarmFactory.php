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
            'code' => 'F' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'name' => $this->faker->company . '-Farm',
            'address' => $this->faker->address,
            'phone_number' => $this->faker->phoneNumber,
            'contact_person' => $this->faker->name,
            'status' => 'active',
            'created_by' => 3,
        ];
    }
}
