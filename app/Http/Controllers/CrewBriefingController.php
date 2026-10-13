<?php

namespace App\Http\Controllers;

use App\Models\CrewLink;
use App\Support\Funnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public crew pages. The token is the only credential, so lookups ignore the manager tenant scope
 * and never expose correct answers before the crew member has submitted.
 */
class CrewBriefingController extends Controller
{
    public function show(string $token): Response
    {
        $link = $this->findLink($token);

        if (! $link->opened_at) {
            $link->update(['opened_at' => now()]);
        }

        $briefing = $link->briefing;
        $completed = (bool) $link->completed_at;

        return Inertia::render('Crew/Briefing', [
            'token' => $token,
            'crewMember' => $link->crewMember->name,
            'briefing' => $briefing->only(['title', 'summary', 'key_actions']),
            'questions' => $briefing->questions->map(fn ($q) => [
                'prompt' => $q->prompt,
                'options' => $q->options,
                ...($completed ? ['correct_index' => $q->correct_index] : []),
            ]),
            'result' => $completed ? [
                'score' => $link->score,
                'total' => $briefing->questions->count(),
                'answers' => $link->answers,
            ] : null,
        ]);
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $link = $this->findLink($token);

        if ($link->completed_at) {
            return back();
        }

        $questions = $link->briefing->questions;

        $data = $request->validate([
            'answers' => 'required|array|size:'.$questions->count(),
            'answers.*' => 'required|integer|min:0|max:3',
        ]);

        $answers = array_map('intval', array_values($data['answers']));
        $score = $questions->values()->filter(fn ($q, $i) => $answers[$i] === $q->correct_index)->count();

        // Conditional update so two concurrent submits cannot both record an attempt.
        $saved = CrewLink::withoutGlobalScope('company')
            ->whereKey($link->id)
            ->whereNull('completed_at')
            ->update(['answers' => json_encode($answers), 'score' => $score, 'completed_at' => now()]);

        if ($saved) {
            Funnel::record($link->company_id, 'first_completion');
        }

        return back();
    }

    private function findLink(string $token): CrewLink
    {
        $link = CrewLink::withoutGlobalScope('company')
            ->with(['briefing' => fn ($q) => $q->withoutGlobalScope('company'), 'briefing.questions', 'crewMember' => fn ($q) => $q->withoutGlobalScope('company')])
            ->where('token', $token)
            ->first();

        abort_unless($link && $link->briefing?->isPublished() && $link->crewMember, 404);

        return $link;
    }
}
