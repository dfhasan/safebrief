<?php

namespace Database\Factories;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'trial_ends_at' => now()->addDays(Company::TRIAL_DAYS),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn () => ['trial_ends_at' => now()->subDay()]);
    }
}
