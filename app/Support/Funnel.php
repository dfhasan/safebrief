<?php

namespace App\Support;

use App\Models\FunnelEvent;

/**
 * Trial funnel. Each step is recorded once per company, at its first occurrence.
 */
class Funnel
{
    public const STEPS = [
        'signed_up' => 'Signed up',
        'first_briefing' => 'First briefing drafted',
        'published' => 'First briefing published',
        'crew_invited' => 'Crew invited',
        'first_completion' => 'First crew completion',
    ];

    public static function record(int $companyId, string $step): void
    {
        FunnelEvent::firstOrCreate(
            ['company_id' => $companyId, 'name' => $step],
            ['occurred_at' => now()],
        );
    }
}
