<?php

namespace App\Http\Controllers;

use App\Models\CrewMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrewMemberController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Crew/Index', [
            'crewMembers' => CrewMember::orderBy('name')
                ->withCount(['crewLinks', 'crewLinks as completed_count' => fn ($q) => $q->whereNotNull('completed_at')])
                ->get(['id', 'name', 'contact']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        CrewMember::create($request->validate([
            'name' => 'required|string|max:120',
            'contact' => 'nullable|string|max:120',
        ]));

        return back()->with('status', __('Crew member added.'));
    }

    public function destroy(CrewMember $crewMember): RedirectResponse
    {
        $crewMember->delete();

        return back()->with('status', __('Crew member removed.'));
    }
}
