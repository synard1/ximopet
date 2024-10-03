<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Kandang;

class KandangFactory extends Factory
{
    protected $model = Kandang::class;

    public function definition()
    {
        return [
            'nama' => 'Kandang-' . $this->faker->unique()->word, 
            'jumlah' => 0,
            'kapasitas' => 100000, // Adjust as needed
            'status' => 'Aktif',
            'user_id' => 1, // Adjust if needed
        ];
    }
}
