<?php

namespace App\Http\Controllers;

use App\Ai\BriefingDraftService;
use App\Ai\InvalidDraftException;
use App\Models\Briefing;
use App\Models\CrewMember;
use App\Support\Funnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class BriefingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Briefings/Index', [
            'briefings' => Briefing::latest()
                ->withCount(['crewLinks', 'crewLinks as completed_count' => fn ($q) => $q->whereNotNull('completed_at')])
                ->get(['id', 'title', 'status', 'published_at', 'created_at']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Briefings/Create');
    }

    public function store(Request $request, BriefingDraftService $drafts): RedirectResponse
    {
        $data = $request->validate(['incident_text' => 'required|string|min:30|max:10000']);

        try {
            $draft = $drafts->generate($data['incident_text']);
        } catch (InvalidDraftException) {
            return back()->withErrors([
                'incident_text' => __('The AI could not produce a valid draft. Try again, or add more detail to the report.'),
            ])->withInput();
        }

        $briefing = DB::transaction(function () use ($request, $data, $draft) {
            $briefing = Briefing::create([
                'user_id' => $request->user()->id,
                'incident_text' => $data['incident_text'],
                'title' => $draft['title'],
                'summary' => $draft['summary'],
                'key_actions' => $draft['key_actions'],
                'status' => 'draft',
            ]);

            $this->replaceQuestions($briefing, $draft['questions']);

            return $briefing;
        });

        Funnel::record($briefing->company_id, 'first_briefing');

        // The draft page itself explains what to check, so no extra flash message here.
        return redirect()->route('briefings.show', $briefing);
    }

    public function show(Briefing $briefing): Response
    {
        $briefing->load(['questions', 'crewLinks.crewMember']);

        return Inertia::render('Briefings/Show', [
            'briefing' => [
                ...$briefing->only(['id', 'title', 'summary', 'key_actions', 'incident_text', 'status', 'published_at']),
                'questions' => $briefing->questions->map->only(['prompt', 'options', 'correct_index']),
            ],
            'links' => $briefing->crewLinks->map(fn ($link) => [
                'id' => $link->id,
                'crew_member_id' => $link->crew_member_id,
                'crew_member' => $link->crewMember->name,
                'url' => route('crew.show', $link->token),
                'opened_at' => $link->opened_at,
                'completed_at' => $link->completed_at,
                'score' => $link->score,
            ]),
            'crewMembers' => CrewMember::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Briefing $briefing): RedirectResponse
    {
        abort_if($briefing->isPublished(), 403, 'Published briefings cannot be edited.');

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'summary' => 'required|string|max:1500',
            'key_actions' => 'required|array|min:1|max:6',
            'key_actions.*' => 'required|string|max:250',
            'questions' => 'required|array|size:3',
            'questions.*.prompt' => 'required|string|max:255',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.options.*' => 'required|string|max:150',
            'questions.*.correct_index' => 'required|integer|min:0|max:3',
        ]);

        foreach ($data['questions'] as $i => $question) {
            if ($question['correct_index'] >= count($question['options'])) {
                return back()->withErrors(["questions.{$i}.correct_index" => __('Pick one of the options as the correct answer.')]);
            }
        }

        DB::transaction(function () use ($briefing, $data) {
            $briefing->update([
                'title' => $data['title'],
                'summary' => $data['summary'],
                'key_actions' => array_values($data['key_actions']),
            ]);
            $this->replaceQuestions($briefing, $data['questions']);
        });

        return back()->with('status', __('Draft saved.'));
    }

    public function publish(Briefing $briefing): RedirectResponse
    {
        if (! $briefing->isPublished()) {
            $briefing->update(['status' => 'published', 'published_at' => now()]);
            Funnel::record($briefing->company_id, 'published');
        }

        return back()->with('status', __('Published. Now invite your crew.'));
    }

    private function replaceQuestions(Briefing $briefing, array $questions): void
    {
        $briefing->questions()->delete();

        foreach (array_values($questions) as $i => $question) {
            $briefing->questions()->create([
                'position' => $i + 1,
                'prompt' => $question['prompt'],
                'options' => array_values($question['options']),
                'correct_index' => $question['correct_index'],
            ]);
        }
    }
}
