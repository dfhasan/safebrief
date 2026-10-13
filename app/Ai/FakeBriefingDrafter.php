<?php

namespace App\Ai;

use Illuminate\Support\Str;

/**
 * Deterministic drafter for local dev and tests: no API key, no network.
 */
class FakeBriefingDrafter implements BriefingDrafter
{
    public function draft(string $incidentText, ?string $previousError = null): string
    {
        $text = Str::squish($incidentText);
        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];

        return json_encode([
            'title' => __('Briefing:').' '.Str::limit(Str::words($sentences[0], 8, ''), 80, ''),
            'summary' => Str::limit(implode(' ', array_slice($sentences, 0, 2)), 600),
            'key_actions' => [
                __('Stop work and make the area safe if this situation repeats.'),
                __('Report every near miss to your supervisor the same shift.'),
                __('Check your PPE and isolation before starting the task.'),
            ],
            'questions' => [
                [
                    'prompt' => __('What is the first thing to do if this happens again?'),
                    'options' => [__('Stop work and make the area safe'), __('Finish the task quickly'), __('Wait for the next shift')],
                    'correct_index' => 0,
                ],
                [
                    'prompt' => __('When should a near miss be reported?'),
                    'options' => [__('At the end of the month'), __('The same shift'), __('Only if someone is hurt')],
                    'correct_index' => 1,
                ],
                [
                    'prompt' => __('What do you check before starting the task?'),
                    'options' => [__('Nothing, it is routine'), __('Only the weather'), __('PPE and isolation')],
                    'correct_index' => 2,
                ],
            ],
        ]);
    }
}
