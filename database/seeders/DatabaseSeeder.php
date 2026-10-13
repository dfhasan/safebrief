<?php

namespace Database\Seeders;

use App\Models\Briefing;
use App\Models\Company;
use App\Models\CrewLink;
use App\Models\CrewMember;
use App\Models\FunnelEvent;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Demo tenant: demo@safebrief.test / password.
     */
    public function run(): void
    {
        $company = Company::create(['name' => 'Northwind Construction', 'trial_ends_at' => now()->addDays(10)]);

        $manager = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Demo Manager',
            'email' => 'demo@safebrief.test',
        ]);

        $crew = collect(['Ali Hassan', 'Maria Lopez', 'Tom Becker', 'Sara Khan', 'Jon Park'])
            ->map(fn ($name) => CrewMember::create(['company_id' => $company->id, 'name' => $name]));

        $published = Briefing::create([
            'company_id' => $company->id,
            'user_id' => $manager->id,
            'incident_text' => 'Near miss on level 3: an unsecured scaffold board slid off the edge during high wind and landed in the walkway below. No one was hurt. The board had not been tied in after the morning inspection.',
            'title' => 'Tie in scaffold boards before you leave them',
            'summary' => 'An untied scaffold board on level 3 blew off in high wind and fell into a walkway. Nobody was hurt, but it could have been fatal. The board was not tied in after the morning inspection.',
            'key_actions' => [
                'Tie in every scaffold board before you step away from it.',
                'Stop work at height when wind picks up and tell your supervisor.',
                'Keep the exclusion zone below scaffold work taped off.',
            ],
            'status' => 'published',
            'published_at' => now()->subDays(3),
        ]);

        $questions = [
            ['When must a scaffold board be tied in?', ['Before you step away from it', 'At the end of the day', 'Only in high wind'], 0],
            ['What do you do when wind picks up during work at height?', ['Work faster', 'Stop and tell your supervisor', 'Carry on carefully'], 1],
            ['What keeps people safe below scaffold work?', ['A warning shout', 'Hard hats only', 'A taped-off exclusion zone'], 2],
        ];

        foreach ($questions as $i => [$prompt, $options, $correct]) {
            $published->questions()->create(['position' => $i + 1, 'prompt' => $prompt, 'options' => $options, 'correct_index' => $correct]);
        }

        foreach ($crew->take(4) as $i => $member) {
            CrewLink::create([
                'company_id' => $company->id,
                'briefing_id' => $published->id,
                'crew_member_id' => $member->id,
                'token' => CrewLink::newToken(),
                'opened_at' => $i < 3 ? now()->subDays(2) : null,
                'completed_at' => $i < 2 ? now()->subDays(2) : null,
                'answers' => $i < 2 ? [0, 1, $i === 0 ? 2 : 0] : null,
                'score' => $i < 2 ? ($i === 0 ? 3 : 2) : null,
            ]);
        }

        $draft = Briefing::create([
            'company_id' => $company->id,
            'user_id' => $manager->id,
            'incident_text' => 'Forklift reversed out of bay 2 without a banksman and nearly struck a pedestrian carrying boxes.',
            'title' => 'Briefing: Forklift reversed out of bay 2 without a banksman',
            'summary' => 'A forklift reversed out of bay 2 without a banksman and nearly struck a pedestrian.',
            'key_actions' => ['Always use a banksman when reversing out of a bay.'],
            'status' => 'draft',
        ]);

        foreach ($questions as $i => [$prompt, $options, $correct]) {
            $draft->questions()->create(['position' => $i + 1, 'prompt' => $prompt, 'options' => $options, 'correct_index' => $correct]);
        }

        foreach ([
            'signed_up' => now()->subDays(4),
            'first_briefing' => now()->subDays(4)->addMinutes(6),
            'published' => now()->subDays(3),
            'crew_invited' => now()->subDays(3)->addMinutes(2),
            'first_completion' => now()->subDays(2),
        ] as $step => $at) {
            FunnelEvent::create(['company_id' => $company->id, 'name' => $step, 'occurred_at' => $at]);
        }
    }
}
