<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Company;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition()
    {
        return [
            'name' => $this->faker->company,
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->email,
            'domain' => $this->faker->domainName,
            'database' => $this->faker->word,
            'package' => 'basic',
            'status' => 'active',
            'notes' => $this->faker->sentence,
        ];
    }
}
