<?php

namespace App\Services\Llm;

/** Keeps the app useful without credentials, while clearly not claiming an LLM decision. */
class RetrievalOnlyLlmClient implements LlmClient
{
    public function confirmDuplicate(string $source, string $candidate): LlmConfirmation
    {
        return new LlmConfirmation(
            confirmed: false,
            reason: 'LLM confirmation is not configured; this is a lexical match only.',
        );
    }
}
