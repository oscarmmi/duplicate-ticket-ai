<?php

namespace App\Services\Llm;

interface LlmClient
{
    public function confirmDuplicate(string $source, string $candidate): LlmConfirmation;
}
