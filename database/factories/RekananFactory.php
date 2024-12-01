<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Rekanan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Rekanan>
 */
class RekananFactory extends Factory
{
    protected $model = Rekanan::class;

    public function definition()
    {
        return [
            'nama' => $this->faker->company,
            'alamat' => $this->faker->address,
            'telp' => $this->faker->phoneNumber,
            'pic' => $this->faker->name,
            'telp_pic' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'status' => 'Aktif',
            'created_by' => 3,
        ];
    }
}
