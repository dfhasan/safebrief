<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class TrialGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_up_starts_a_14_day_trial_and_records_the_funnel_step(): void
    {
        $this->post('/register', [
            'company_name' => 'Acme Rail',
            'name' => 'Pat',
            'email' => 'pat@acme.test',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect('/dashboard');

        $company = Company::firstWhere('name', 'Acme Rail');

        $this->assertEqualsWithDelta(14, now()->diffInDays($company->trial_ends_at), 0.01);
        $this->assertSame($company->id, User::firstWhere('email', 'pat@acme.test')->company_id);
        $this->assertDatabaseHas('funnel_events', ['company_id' => $company->id, 'name' => 'signed_up']);
    }

    public function test_active_trial_reaches_the_app(): void
    {
        $this->actingAs(User::factory()->create())->get('/dashboard')->assertOk();
    }

    public function test_expired_trial_is_sent_to_the_trial_ended_page(): void
    {
        $user = User::factory()->for(Company::factory()->expired())->create();

        foreach (['/dashboard', '/briefings', '/briefings/create', '/crew'] as $url) {
            $this->actingAs($user)->get($url)->assertRedirect('/trial-expired');
        }

        $this->actingAs($user)->post('/briefings', ['incident_text' => str_repeat('x', 40)])
            ->assertRedirect('/trial-expired');

        $this->actingAs($user)->get('/trial-expired')->assertOk();
    }

    public function test_expired_trial_does_not_break_crew_links(): void
    {
        // Crew pages are token-gated, not trial-gated: a crew member should still read an already-sent briefing.
        $company = Company::factory()->expired()->create();
        $link = \App\Models\CrewLink::create([
            'company_id' => $company->id,
            'briefing_id' => \App\Models\Briefing::factory()->published()->withQuestions()->create(['company_id' => $company->id])->id,
            'crew_member_id' => \App\Models\CrewMember::factory()->create(['company_id' => $company->id])->id,
            'token' => \App\Models\CrewLink::newToken(),
        ]);

        $this->get("/b/{$link->token}")->assertOk();
    }

    public function test_sign_up_stores_the_email_lower_case_instead_of_rejecting_capitals(): void
    {
        $this->post('/register', [
            'company_name' => 'Harbour Works',
            'name' => 'Erin',
            'email' => '  Erin.Walsh@HarbourWorks.TEST ',
            'password' => 'harbour-safe-2026',
            'password_confirmation' => 'harbour-safe-2026',
        ])->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', ['email' => 'erin.walsh@harbourworks.test']);

        auth()->logout();
        $this->post('/login', ['email' => 'ERIN.WALSH@harbourworks.test', 'password' => 'harbour-safe-2026'])
            ->assertRedirect('/dashboard');
    }

    public function test_active_trial_cannot_see_the_trial_ended_page(): void
    {
        $this->actingAs(User::factory()->create())->get('/trial-expired')->assertRedirect('/dashboard');
    }

    public function test_trial_end_date_and_contact_are_shared_and_setup_steps_hide_after_expiry(): void
    {
        $active = User::factory()->create();
        $this->actingAs($active)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('trial.endsAt', $active->company->trial_ends_at->toIso8601String())
            ->where('trial.contactEmail', config('services.safebrief.contact_email'))
            ->whereNot('onboarding', null));

        $expired = User::factory()->for(Company::factory()->expired())->create();
        $this->actingAs($expired)->get('/trial-expired')->assertInertia(fn (Assert $page) => $page
            ->where('trial.active', false)
            ->where('onboarding', null));
    }
}
