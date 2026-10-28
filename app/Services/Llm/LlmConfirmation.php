<?php

namespace App\Services\Llm;

final readonly class LlmConfirmation
{
    public function __construct(
        public bool $confirmed,
        public string $reason,
    ) {}
}
