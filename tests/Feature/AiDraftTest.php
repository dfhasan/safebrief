<?php

namespace Tests\Feature;

use App\Ai\BriefingDrafter;
use App\Ai\BriefingDraftService;
use App\Ai\FakeBriefingDrafter;
use App\Ai\InvalidDraftException;
use App\Models\Briefing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiDraftTest extends TestCase
{
    use RefreshDatabase;

    private const INCIDENT = 'Near miss: a forklift reversed out of bay 2 without a banksman and nearly hit a pedestrian.';

    /** Drafter that returns queued responses and records how often it was called. */
    private function scripted(array $responses): BriefingDrafter
    {
        return new class($responses) implements BriefingDrafter
        {
            public int $calls = 0;

            public array $errors = [];

            public function __construct(private array $responses) {}

            public function draft(string $incidentText, ?string $previousError = null): string
            {
                $this->errors[] = $previousError;

                return $this->responses[$this->calls++];
            }
        };
    }

    private function valid(): string
    {
        return (new FakeBriefingDrafter)->draft(self::INCIDENT);
    }

    public function test_valid_output_is_accepted_first_time(): void
    {
        $drafter = $this->scripted([$this->valid()]);

        $draft = (new BriefingDraftService($drafter))->generate(self::INCIDENT);

        $this->assertSame(1, $drafter->calls);
        $this->assertCount(3, $draft['questions']);
    }

    public function test_invalid_json_is_retried_once_with_the_reason(): void
    {
        $drafter = $this->scripted(['Sure! Here is your briefing:', $this->valid()]);

        (new BriefingDraftService($drafter))->generate(self::INCIDENT);

        $this->assertSame(2, $drafter->calls);
        $this->assertNull($drafter->errors[0]);
        $this->assertSame('output was not a JSON object', $drafter->errors[1]);
    }

    public function test_markdown_fenced_json_is_accepted(): void
    {
        $draft = (new BriefingDraftService($this->scripted(["```json\n".$this->valid()."\n```"])))->generate(self::INCIDENT);

        $this->assertNotEmpty($draft['title']);
    }

    public function test_two_bad_outputs_throw(): void
    {
        $this->expectException(InvalidDraftException::class);

        (new BriefingDraftService($this->scripted(['nope', '{"title": "x"}'])))->generate(self::INCIDENT);
    }

    public function test_schema_violations_are_rejected(): void
    {
        $service = new BriefingDraftService(new FakeBriefingDrafter);
        $base = json_decode($this->valid(), true);

        $twoQuestions = $base;
        array_pop($twoQuestions['questions']);

        $badIndex = $base;
        $badIndex['questions'][0]['correct_index'] = 5;

        $noActions = $base;
        $noActions['key_actions'] = [];

        foreach ([$twoQuestions, $badIndex, $noActions] as $case) {
            [$draft, $error] = $service->parse(json_encode($case));
            $this->assertNull($draft);
            $this->assertNotNull($error);
        }
    }

    public function test_drafter_exception_counts_as_a_failed_attempt(): void
    {
        $drafter = new class implements BriefingDrafter
        {
            public int $calls = 0;

            public function draft(string $incidentText, ?string $previousError = null): string
            {
                if ($this->calls++ === 0) {
                    throw new \RuntimeException('timeout');
                }

                return (new FakeBriefingDrafter)->draft($incidentText);
            }
        };

        (new BriefingDraftService($drafter))->generate(self::INCIDENT);

        $this->assertSame(2, $drafter->calls);
    }

    public function test_store_always_saves_a_draft_never_published(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/briefings', ['incident_text' => self::INCIDENT]);

        $briefing = Briefing::sole();
        $response->assertRedirect("/briefings/{$briefing->id}");
        $this->assertSame('draft', $briefing->status);
        $this->assertNull($briefing->published_at);
        $this->assertCount(3, $briefing->questions);
        $this->assertDatabaseHas('funnel_events', ['company_id' => $user->company_id, 'name' => 'first_briefing']);
    }

    public function test_store_shows_an_error_and_saves_nothing_when_ai_fails_twice(): void
    {
        $this->app->instance(BriefingDrafter::class, $this->scripted(['bad', 'still bad']));

        $this->actingAs(User::factory()->create())
            ->post('/briefings', ['incident_text' => self::INCIDENT])
            ->assertSessionHasErrors('incident_text');

        $this->assertDatabaseCount('briefings', 0);
    }

    public function test_published_briefing_cannot_be_edited(): void
    {
        $user = User::factory()->create();
        $briefing = Briefing::factory()->published()->withQuestions()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        $payload = json_decode($this->valid(), true);

        $this->actingAs($user)->put("/briefings/{$briefing->id}", $payload)->assertForbidden();
    }

    public function test_manager_can_edit_then_publish_a_draft(): void
    {
        $user = User::factory()->create();
        $briefing = Briefing::factory()->withQuestions()->create(['company_id' => $user->company_id, 'user_id' => $user->id]);
        $payload = json_decode($this->valid(), true);
        $payload['title'] = 'Edited by manager';

        $this->actingAs($user)->put("/briefings/{$briefing->id}", $payload)->assertSessionHasNoErrors();
        $this->actingAs($user)->post("/briefings/{$briefing->id}/publish");

        $briefing->refresh();
        $this->assertSame('Edited by manager', $briefing->title);
        $this->assertSame('published', $briefing->status);
        $this->assertDatabaseHas('funnel_events', ['company_id' => $user->company_id, 'name' => 'published']);
    }
}
