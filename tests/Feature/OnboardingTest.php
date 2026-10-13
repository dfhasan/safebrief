<?php

namespace Tests\Feature;

use App\Models\Briefing;
use App\Models\CrewLink;
use App\Models\CrewMember;
use App\Models\User;
use App\Support\Onboarding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private function current(User $user): ?string
    {
        return Onboarding::for($user->company->fresh())['current'];
    }

    public function test_steps_advance_in_order_until_the_first_completion(): void
    {
        $user = User::factory()->create();
        $this->assertSame('crew', $this->current($user));

        $member = CrewMember::factory()->create(['company_id' => $user->company_id]);
        $this->assertSame('report', $this->current($user));

        $briefing = Briefing::factory()->withQuestions()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        $this->assertSame('publish', $this->current($user));
        $this->assertSame(route('briefings.show', $briefing), Onboarding::for($user->company)['steps'][2]['href']);

        $briefing->update(['status' => 'published', 'published_at' => now()]);
        $this->assertSame('invite', $this->current($user));

        $link = CrewLink::create([
            'company_id' => $user->company_id,
            'briefing_id' => $briefing->id,
            'crew_member_id' => $member->id,
            'token' => CrewLink::newToken(),
        ]);
        $this->assertSame('complete', $this->current($user));

        $link->update(['completed_at' => now(), 'score' => 3, 'answers' => [0, 0, 0]]);
        $this->assertNull($this->current($user));
    }

    public function test_trail_only_counts_the_managers_own_company(): void
    {
        $user = User::factory()->create();
        CrewMember::factory()->create(); // another company's crew
        Briefing::factory()->published()->create(); // another company's briefing

        $this->assertSame('crew', $this->current($user));
    }

    public function test_trail_is_shared_with_pages_for_managers_only(): void
    {
        $this->get('/login')->assertInertia(fn (Assert $page) => $page->where('onboarding', null));

        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertInertia(fn (Assert $page) => $page->where('onboarding.current', 'crew')->has('onboarding.steps', 5));
    }
}
