<?php

namespace Database\Factories;

use App\Models\Briefing;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Briefing>
 */
class BriefingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => fn (array $attributes) => User::factory()->create(['company_id' => $attributes['company_id']])->id,
            'incident_text' => fake()->paragraph(),
            'title' => fake()->sentence(4),
            'summary' => fake()->paragraph(),
            'key_actions' => [fake()->sentence(), fake()->sentence()],
            'status' => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => ['status' => 'published', 'published_at' => now()]);
    }

    public function withQuestions(): static
    {
        return $this->afterCreating(function (Briefing $briefing) {
            foreach (range(1, 3) as $position) {
                $briefing->questions()->create([
                    'position' => $position,
                    'prompt' => "Question {$position}?",
                    'options' => ['A', 'B', 'C'],
                    'correct_index' => 0,
                ]);
            }
        });
    }
}
