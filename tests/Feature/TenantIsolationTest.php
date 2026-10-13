<?php

namespace Tests\Feature;

use App\Models\Briefing;
use App\Models\CrewMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private Briefing $foreignBriefing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = User::factory()->create();
        $this->foreignBriefing = Briefing::factory()->published()->withQuestions()->create();
    }

    public function test_briefing_list_only_shows_own_company(): void
    {
        $own = Briefing::factory()->create(['company_id' => $this->manager->company_id, 'user_id' => $this->manager->id]);

        $this->actingAs($this->manager)->get('/briefings')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('briefings', 1)
                ->where('briefings.0.id', $own->id));
    }

    public function test_cannot_view_update_publish_or_invite_on_another_companys_briefing(): void
    {
        $id = $this->foreignBriefing->id;

        $this->actingAs($this->manager);
        $this->get("/briefings/{$id}")->assertNotFound();
        $this->put("/briefings/{$id}", ['title' => 'hijacked'])->assertNotFound();
        $this->post("/briefings/{$id}/publish")->assertNotFound();
        $this->post("/briefings/{$id}/links", ['crew_member_ids' => [1]])->assertNotFound();

        $this->assertNotSame('hijacked', $this->foreignBriefing->fresh()->title);
    }

    public function test_cannot_see_or_delete_another_companys_crew(): void
    {
        $foreignCrew = CrewMember::factory()->create();

        $this->actingAs($this->manager)->get('/crew')
            ->assertInertia(fn ($page) => $page->has('crewMembers', 0));

        $this->actingAs($this->manager)->delete("/crew/{$foreignCrew->id}")->assertNotFound();
        $this->assertModelExists($foreignCrew);
    }

    public function test_cannot_invite_another_companys_crew_member(): void
    {
        $own = Briefing::factory()->published()->withQuestions()->create([
            'company_id' => $this->manager->company_id,
            'user_id' => $this->manager->id,
        ]);
        $foreignCrew = CrewMember::factory()->create();

        $this->actingAs($this->manager)
            ->post("/briefings/{$own->id}/links", ['crew_member_ids' => [$foreignCrew->id]])
            ->assertSessionHasErrors('crew_member_ids.0');

        $this->assertDatabaseCount('crew_links', 0);
    }

    public function test_scope_fails_closed_for_a_user_without_a_company(): void
    {
        $this->actingAs(User::factory()->create(['company_id' => null]));

        $this->assertSame(0, Briefing::count());
        $this->assertSame(1, Briefing::withoutGlobalScopes()->count());
    }

    public function test_new_records_are_stamped_with_the_managers_company(): void
    {
        $this->actingAs($this->manager)->post('/crew', ['name' => 'Ali']);

        $this->assertDatabaseHas('crew_members', ['name' => 'Ali', 'company_id' => $this->manager->company_id]);
    }
}
