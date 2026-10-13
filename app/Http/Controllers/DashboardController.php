<?php

namespace App\Http\Controllers;

use App\Models\Briefing;
use App\Models\FunnelEvent;
use App\Support\Funnel;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $events = FunnelEvent::where('company_id', $request->user()->company_id)->pluck('occurred_at', 'name');

        $start = $events['signed_up'] ?? null;
        $firstValue = $events['first_completion'] ?? null;

        return Inertia::render('Dashboard', [
            'briefings' => Briefing::where('status', 'published')
                ->latest('published_at')
                ->withCount(['crewLinks', 'crewLinks as completed_count' => fn ($q) => $q->whereNotNull('completed_at')])
                ->withAvg(['crewLinks as average_score' => fn ($q) => $q->whereNotNull('completed_at')], 'score')
                ->get(['id', 'title', 'published_at']),
            'draftCount' => Briefing::where('status', 'draft')->count(),
            'funnel' => collect(Funnel::STEPS)->map(fn ($label, $step) => [
                'step' => $step,
                'label' => $label,
                'at' => $events[$step] ?? null,
            ])->values(),
            'timeToFirstValue' => $start && $firstValue
                ? $start->diffForHumans($firstValue, ['syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE, 'parts' => 2])
                : null,
        ]);
    }
}
