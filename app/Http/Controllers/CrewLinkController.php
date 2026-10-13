<?php

namespace App\Http\Controllers;

use App\Models\Briefing;
use App\Models\CrewLink;
use App\Support\Funnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CrewLinkController extends Controller
{
    public function store(Request $request, Briefing $briefing): RedirectResponse
    {
        abort_unless($briefing->isPublished(), 403, 'Publish the briefing before inviting crew.');

        $data = $request->validate([
            'crew_member_ids' => 'required|array|min:1',
            'crew_member_ids.*' => [
                'integer',
                Rule::exists('crew_members', 'id')->where('company_id', $briefing->company_id),
            ],
        ]);

        foreach (array_unique($data['crew_member_ids']) as $crewMemberId) {
            CrewLink::firstOrCreate(
                ['briefing_id' => $briefing->id, 'crew_member_id' => $crewMemberId],
                ['company_id' => $briefing->company_id, 'token' => CrewLink::newToken()],
            );
        }

        Funnel::record($briefing->company_id, 'crew_invited');

        return back()->with('status', __('Links created. Copy each link and send it to the crew member.'));
    }
}
