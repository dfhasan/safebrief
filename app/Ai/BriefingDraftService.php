<?php

namespace App\Ai;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

/**
 * Asks the drafter for a briefing, validates the JSON strictly, and retries once with the reason.
 * Never publishes: callers must save the result as a draft for a manager to review.
 */
class BriefingDraftService
{
    public const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'title' => ['type' => 'string'],
            'summary' => ['type' => 'string'],
            'key_actions' => ['type' => 'array', 'items' => ['type' => 'string']],
            'questions' => [
                'type' => 'array',
                'items' => [
                    'type' => 'object',
                    'properties' => [
                        'prompt' => ['type' => 'string'],
                        'options' => ['type' => 'array', 'items' => ['type' => 'string']],
                        'correct_index' => ['type' => 'integer'],
                    ],
                    'required' => ['prompt', 'options', 'correct_index'],
                    'additionalProperties' => false,
                ],
            ],
        ],
        'required' => ['title', 'summary', 'key_actions', 'questions'],
        'additionalProperties' => false,
    ];

    public function __construct(private BriefingDrafter $drafter) {}

    /**
     * @return array{title: string, summary: string, key_actions: list<string>, questions: list<array{prompt: string, options: list<string>, correct_index: int}>}
     *
     * @throws InvalidDraftException after two rejected attempts
     */
    public function generate(string $incidentText): array
    {
        $error = null;

        foreach ([1, 2] as $attempt) {
            try {
                $raw = $this->drafter->draft($incidentText, $error);
            } catch (Throwable $e) {
                Log::warning('Briefing drafter failed', ['attempt' => $attempt, 'error' => $e->getMessage()]);
                $error = 'the drafting service errored';

                continue;
            }

            [$draft, $error] = $this->parse($raw);

            if ($draft) {
                return $draft;
            }

            Log::info('Briefing draft rejected', ['attempt' => $attempt, 'reason' => $error]);
        }

        throw new InvalidDraftException("AI draft rejected twice: {$error}");
    }

    /**
     * @return array{0: ?array, 1: ?string} [draft, error]
     */
    public function parse(string $raw): array
    {
        $json = trim(preg_replace('/^```(?:json)?\s*|\s*```$/', '', trim($raw)));
        $data = json_decode($json, true);

        if (! is_array($data)) {
            return [null, 'output was not a JSON object'];
        }

        $validator = Validator::make($data, [
            'title' => 'required|string|max:120',
            'summary' => 'required|string|max:1500',
            'key_actions' => 'required|array|min:1|max:6',
            'key_actions.*' => 'required|string|max:250',
            'questions' => 'required|array|size:3',
            'questions.*.prompt' => 'required|string|max:255',
            'questions.*.options' => 'required|array|min:2|max:4',
            'questions.*.options.*' => 'required|string|max:150',
            'questions.*.correct_index' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return [null, $validator->errors()->first()];
        }

        foreach ($data['questions'] as $i => $question) {
            if ($question['correct_index'] >= count($question['options'])) {
                return [null, 'question '.($i + 1).' has a correct_index outside its options'];
            }
        }

        return [[
            'title' => $data['title'],
            'summary' => $data['summary'],
            'key_actions' => array_values($data['key_actions']),
            'questions' => array_map(fn ($q) => [
                'prompt' => $q['prompt'],
                'options' => array_values($q['options']),
                'correct_index' => $q['correct_index'],
            ], array_values($data['questions'])),
        ], null];
    }
}
