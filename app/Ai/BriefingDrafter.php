<?php

namespace App\Ai;

/**
 * Turns an incident report into raw model output (expected to be JSON matching BriefingDraft::SCHEMA).
 * Implementations return the text as-is; BriefingDraftService owns parsing and validation.
 */
interface BriefingDrafter
{
    /**
     * @param  string|null  $previousError  why the previous attempt was rejected, so a retry can correct it
     */
    public function draft(string $incidentText, ?string $previousError = null): string;
}
