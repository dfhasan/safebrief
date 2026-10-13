<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\CrewMember;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrewMember>
 */
class CrewMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->name(),
            'contact' => fake()->phoneNumber(),
        ];
    }
}
