<?php

namespace App\Services\Llm;

/** Deterministic test double: confirms candidates whose lexical signal is strong. */
class FakeLlmClient implements LlmClient
{
    /** @var list<array{source: string, candidate: string}> */
    public array $requests = [];

    public function __construct(private readonly float $confirmationThreshold = 0.35) {}

    public function confirmDuplicate(string $source, string $candidate): LlmConfirmation
    {
        $this->requests[] = ['source' => $source, 'candidate' => $candidate];
        $confirmed = $this->overlap($source, $candidate) >= $this->confirmationThreshold;

        return new LlmConfirmation(
            $confirmed,
            $confirmed
                ? 'The tickets describe the same customer issue and expected outcome.'
                : 'The tickets share context but describe different customer issues.',
        );
    }

    private function overlap(string $source, string $candidate): float
    {
        $sourceTokens = array_unique(preg_split('/[^a-z0-9]+/i', strtolower($source), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $candidateTokens = array_unique(preg_split('/[^a-z0-9]+/i', strtolower($candidate), -1, PREG_SPLIT_NO_EMPTY) ?: []);
        $shared = array_intersect($sourceTokens, $candidateTokens);
        $union = array_unique([...$sourceTokens, ...$candidateTokens]);

        return count($union) === 0 ? 0.0 : count($shared) / count($union);
    }
}
