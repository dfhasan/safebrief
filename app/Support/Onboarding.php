<?php

namespace App\Support;

use App\Models\Briefing;
use App\Models\Company;
use App\Models\CrewLink;
use App\Models\CrewMember;

/**
 * The manager's setup trail: what is done, and the one thing to do next.
 * Queries filter by company explicitly so the result never depends on the auth scope.
 */
class Onboarding
{
    /**
     * @return array{steps: list<array{key: string, done: bool, href: string}>, current: ?string}
     */
    public static function for(Company $company): array
    {
        $briefings = Briefing::withoutGlobalScope('company')->where('company_id', $company->id);
        $links = CrewLink::withoutGlobalScope('company')->where('company_id', $company->id);

        $latestDraft = (clone $briefings)->where('status', 'draft')->latest('id')->value('id');
        $latestPublished = (clone $briefings)->where('status', 'published')->latest('id')->value('id');

        $steps = [
            [
                'key' => 'crew',
                'done' => CrewMember::withoutGlobalScope('company')->where('company_id', $company->id)->exists(),
                'href' => route('crew.index'),
            ],
            [
                'key' => 'report',
                'done' => (clone $briefings)->exists(),
                'href' => route('briefings.create'),
            ],
            [
                'key' => 'publish',
                'done' => (bool) $latestPublished,
                'href' => $latestDraft ? route('briefings.show', $latestDraft) : route('briefings.create'),
            ],
            [
                'key' => 'invite',
                'done' => (clone $links)->exists(),
                'href' => $latestPublished ? route('briefings.show', $latestPublished) : route('briefings.index'),
            ],
            [
                'key' => 'complete',
                'done' => (clone $links)->whereNotNull('completed_at')->exists(),
                'href' => ($id = (clone $links)->latest('id')->value('briefing_id'))
                    ? route('briefings.show', $id)
                    : route('briefings.index'),
            ],
        ];

        $current = collect($steps)->firstWhere('done', false)['key'] ?? null;

        return ['steps' => $steps, 'current' => $current];
    }
}
