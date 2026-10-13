<?php

namespace Tests\Feature;

use App\Models\Briefing;
use App\Models\CrewLink;
use App\Models\CrewMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrewLinkAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Briefing $briefing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create();
        $this->briefing = Briefing::factory()->published()->withQuestions()->create([
            'company_id' => $this->manager->company_id,
            'user_id' => $this->manager->id,
        ]);
    }

    private function invite(): CrewLink
    {
        $member = CrewMember::factory()->create(['company_id' => $this->manager->company_id]);

        $this->actingAs($this->manager)
            ->post("/briefings/{$this->briefing->id}/links", ['crew_member_ids' => [$member->id]]);

        auth()->logout();

        return CrewLink::withoutGlobalScopes()->where('crew_member_id', $member->id)->sole();
    }

    public function test_invite_creates_a_long_random_token_and_records_the_funnel_step(): void
    {
        $link = $this->invite();

        $this->assertSame(48, strlen($link->token));
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]+$/', $link->token);
        $this->assertDatabaseHas('funnel_events', ['company_id' => $this->manager->company_id, 'name' => 'crew_invited']);
    }

    public function test_cannot_invite_crew_to_a_draft(): void
    {
        $draft = Briefing::factory()->withQuestions()->create(['company_id' => $this->manager->company_id, 'user_id' => $this->manager->id]);
        $member = CrewMember::factory()->create(['company_id' => $this->manager->company_id]);

        $this->actingAs($this->manager)
            ->post("/briefings/{$draft->id}/links", ['crew_member_ids' => [$member->id]])
            ->assertForbidden();
    }

    public function test_valid_token_shows_the_briefing_without_answers_and_marks_it_opened(): void
    {
        $link = $this->invite();

        $this->get("/b/{$link->token}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Crew/Briefing')
                ->where('briefing.title', $this->briefing->title)
                ->has('questions', 3)
                ->missing('questions.0.correct_index')
                ->where('result', null));

        $this->assertNotNull($link->fresh()->opened_at);
    }

    public function test_link_to_an_unpublished_briefing_is_not_found(): void
    {
        $link = $this->invite();
        $this->briefing->update(['status' => 'draft', 'published_at' => null]);
        auth()->logout();

        $this->get("/b/{$link->token}")->assertNotFound();
        $this->post("/b/{$link->token}", ['answers' => [0, 0, 0]])->assertNotFound();
        $this->assertNull($link->fresh()->completed_at);
    }

    public function test_crew_pages_send_no_referrer_and_no_store(): void
    {
        $link = $this->invite();

        $this->get("/b/{$link->token}")
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_unknown_token_is_not_found(): void
    {
        $this->invite();

        $this->get('/b/'.str_repeat('a', 48))->assertNotFound();
        $this->post('/b/'.str_repeat('a', 48), ['answers' => [0, 0, 0]])->assertNotFound();
    }

    public function test_submitting_scores_completes_and_cannot_be_resubmitted(): void
    {
        $link = $this->invite();

        $this->post("/b/{$link->token}", ['answers' => [0, 1, 0]])->assertRedirect();

        $link->refresh();
        $this->assertNotNull($link->completed_at);
        $this->assertSame(2, $link->score); // factory: every correct_index is 0
        $this->assertDatabaseHas('funnel_events', ['company_id' => $this->manager->company_id, 'name' => 'first_completion']);

        $this->post("/b/{$link->token}", ['answers' => [0, 0, 0]]);
        $this->assertSame(2, $link->fresh()->score);

        $this->get("/b/{$link->token}")->assertInertia(fn ($page) => $page
            ->where('result.score', 2)
            ->has('questions.0.correct_index'));
    }

    public function test_answers_must_cover_every_question(): void
    {
        $link = $this->invite();

        $this->post("/b/{$link->token}", ['answers' => [0]])->assertSessionHasErrors('answers');
        $this->assertNull($link->fresh()->completed_at);
    }

    public function test_link_stops_working_when_the_crew_member_is_removed(): void
    {
        $link = $this->invite();

        $this->actingAs($this->manager)->delete("/crew/{$link->crew_member_id}");
        auth()->logout();

        $this->get("/b/{$link->token}")->assertNotFound();
    }

    public function test_dashboard_shows_completion_and_funnel(): void
    {
        $link = $this->invite();
        $this->post("/b/{$link->token}", ['answers' => [0, 0, 0]]);

        $this->actingAs($this->manager)->get('/dashboard')
            ->assertInertia(fn ($page) => $page
                ->where('briefings.0.crew_links_count', 1)
                ->where('briefings.0.completed_count', 1)
                ->where('funnel.4.step', 'first_completion')
                ->whereNot('funnel.4.at', null));
    }
}
