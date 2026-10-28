<?php

namespace App\Services\Llm;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class GeminiLlmClient implements LlmClient
{
    /** @throws ConnectionException|JsonException */
    public function confirmDuplicate(string $source, string $candidate): LlmConfirmation
    {
        $key = (string) config('services.gemini.api_key');
        $model = (string) config('services.gemini.model', 'gemini-2.0-flash');

        $response = Http::connectTimeout(3)
            ->timeout(12)
            ->acceptJson()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'contents' => [[
                    'parts' => [[
                        'text' => "Decide whether these support tickets report the same underlying issue. Return only JSON with boolean confirmed and a concise reason.\n\nTicket A:\n{$source}\n\nTicket B:\n{$candidate}",
                    ]],
                ]],
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                    'responseSchema' => [
                        'type' => 'OBJECT',
                        'properties' => [
                            'confirmed' => ['type' => 'BOOLEAN'],
                            'reason' => ['type' => 'STRING'],
                        ],
                        'required' => ['confirmed', 'reason'],
                    ],
                    'temperature' => 0,
                ],
            ])
            ->throw();

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text)) {
            throw new RuntimeException('Gemini returned no confirmation payload.');
        }

        $result = json_decode($text, true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($result) || ! is_bool($result['confirmed'] ?? null) || ! is_string($result['reason'] ?? null)) {
            throw new RuntimeException('Gemini returned an invalid confirmation payload.');
        }

        return new LlmConfirmation($result['confirmed'], mb_substr($result['reason'], 0, 500));
    }
}
