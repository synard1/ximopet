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
            'kelompok_ternak_id' => null,
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
