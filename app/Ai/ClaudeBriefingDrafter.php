<?php

namespace App\Ai;

use Anthropic\Client;

/**
 * Real drafter. Only bound when AI_DRIVER=claude and ANTHROPIC_API_KEY is set.
 * Uses structured outputs so the response is JSON matching BriefingDraftService::SCHEMA;
 * the service still validates it, because a refusal or truncation yields no usable JSON.
 */
class ClaudeBriefingDrafter implements BriefingDrafter
{
    private const SYSTEM = <<<'TXT'
You write short safety briefings for frontline crews from incident and near-miss reports.
Use plain language a tired worker can read on a phone in two minutes. Do not invent facts that are not in the report.
Name no individuals. Key actions are concrete things the crew should do differently.
Write exactly three multiple-choice questions that check the crew understood the key actions; exactly one option is correct.
TXT;

    public function __construct(private Client $client, private string $model) {}

    public function draft(string $incidentText, ?string $previousError = null): string
    {
        $language = app()->getLocale() === 'ar' ? 'Arabic' : 'English';
        $prompt = "Write the briefing and questions in {$language}.\n\nIncident report:\n\n".$incidentText;

        if ($previousError) {
            $prompt .= "\n\nYour previous draft was rejected: {$previousError}. Fix that.";
        }

        $message = $this->client->messages->create(
            model: $this->model,
            maxTokens: 16000,
            system: self::SYSTEM,
            messages: [['role' => 'user', 'content' => $prompt]],
            outputConfig: [
                'effort' => 'low',
                'format' => ['type' => 'json_schema', 'schema' => BriefingDraftService::SCHEMA],
            ],
        );

        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                return $block->text;
            }
        }

        return '';
    }
}
