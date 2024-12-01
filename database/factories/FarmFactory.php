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
            'kode' => 'F' . str_pad($this->faker->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'nama' => $this->faker->company . '-Farm',
            'alamat' => $this->faker->address,
            'telp' => $this->faker->phoneNumber,
            'pic' => $this->faker->name,
            'telp_pic' => $this->faker->phoneNumber,
            'jumlah' => 0,
            'kapasitas' => 1000000, // Adjust as needed
            'status' => 'Aktif',
            'created_by' => 3,

        ];
    }
}
