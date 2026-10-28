<?php

namespace App\Services\Tickets;

use App\Models\DuplicateCheck;
use App\Models\SupportTicket;
use App\Services\Llm\LlmClient;
use App\Services\Llm\LlmConfirmation;
use Illuminate\Support\Facades\Log;
use Throwable;

class DuplicateFinder
{
    private const float MINIMUM_SCORE = 0.25;

    private const int MAX_MATCHES = 3;

    /** @var list<string> */
    private const array STOP_WORDS = [
        'a', 'about', 'after', 'all', 'an', 'and', 'any', 'are', 'as', 'at', 'be', 'been', 'before', 'but', 'by',
        'can', 'could', 'did', 'do', 'does', 'for', 'from', 'get', 'had', 'has', 'have', 'he', 'her', 'here', 'his',
        'how', 'i', 'if', 'in', 'into', 'is', 'it', 'its', 'me', 'my', 'of', 'on', 'or', 'our', 'please', 'she',
        'so', 'that', 'the', 'their', 'them', 'then', 'there', 'they', 'this', 'to', 'up', 'us', 'was', 'we', 'were',
        'when', 'which', 'who', 'will', 'with', 'would', 'you', 'your',
    ];

    public function __construct(private readonly LlmClient $llm) {}

    public function find(SupportTicket $ticket): DuplicateCheck
    {
        $corpus = SupportTicket::query()->whereKeyNot($ticket->getKey())->get();
        $documents = $corpus->mapWithKeys(fn (SupportTicket $candidate): array => [
            (int) $candidate->getKey() => $this->tokens($candidate),
        ]);
        $sourceTokens = $this->tokens($ticket);
        $allDocuments = [...$documents->all(), (int) $ticket->getKey() => $sourceTokens];
        $inverseDocumentFrequency = $this->inverseDocumentFrequency($allDocuments);
        $sourceVector = $this->vector($sourceTokens, $inverseDocumentFrequency);

        $ranked = $corpus
            ->map(function (SupportTicket $candidate) use ($documents, $inverseDocumentFrequency, $sourceVector): array {
                $tokens = $documents->get((int) $candidate->getKey(), []);
                $score = $this->cosine($sourceVector, $this->vector($tokens, $inverseDocumentFrequency));

                return [
                    'ticket' => $candidate,
                    'score' => $score,
                    'reasons' => $this->sharedTerms($sourceVector, $tokens),
                ];
            })
            ->filter(fn (array $match): bool => $match['score'] >= self::MINIMUM_SCORE)
            ->sortByDesc('score')
            ->take(self::MAX_MATCHES);

        $check = $ticket->duplicateChecks()->create(['status' => 'completed']);

        foreach ($ranked as $match) {
            /** @var SupportTicket $candidate */
            $candidate = $match['ticket'];
            $confirmation = $this->confirm($ticket, $candidate);

            $check->matches()->create([
                'matched_ticket_id' => $candidate->getKey(),
                'score' => round($match['score'], 4),
                'llm_confirmed' => $confirmation->confirmed,
                'reasons' => array_values(array_unique([...$match['reasons'], $confirmation->reason])),
            ]);
        }

        return $check;
    }

    private function confirm(SupportTicket $source, SupportTicket $candidate): LlmConfirmation
    {
        try {
            return $this->llm->confirmDuplicate(
                "Subject: {$source->subject}\n{$source->body}",
                "Subject: {$candidate->subject}\n{$candidate->body}",
            );
        } catch (Throwable $exception) {
            Log::warning('Duplicate confirmation failed; keeping retrieval result.', [
                'ticket_id' => $source->getKey(),
                'matched_ticket_id' => $candidate->getKey(),
                'exception' => $exception::class,
            ]);

            return new LlmConfirmation(false, 'LLM confirmation was unavailable; lexical similarity only.');
        }
    }

    /** @return list<string> */
    private function tokens(SupportTicket $ticket): array
    {
        $text = mb_strtolower($ticket->subject.' '.$ticket->subject.' '.$ticket->body);
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter($tokens, fn (string $token): bool => mb_strlen($token) > 1 && ! in_array($token, self::STOP_WORDS, true)));
    }

    /** @param array<int, list<string>> $documents
     * @return array<string, float>
     */
    private function inverseDocumentFrequency(array $documents): array
    {
        $frequencies = [];

        foreach ($documents as $tokens) {
            foreach (array_unique($tokens) as $token) {
                $frequencies[$token] = ($frequencies[$token] ?? 0) + 1;
            }
        }

        $count = max(count($documents), 1);

        return array_map(fn (int $frequency): float => log(1 + ($count + 1) / ($frequency + 1)), $frequencies);
    }

    /** @param list<string> $tokens
     * @param  array<string, float>  $idf
     * @return array<string, float>
     */
    private function vector(array $tokens, array $idf): array
    {
        if ($tokens === []) {
            return [];
        }

        $termFrequencies = array_count_values($tokens);
        $total = count($tokens);

        foreach ($termFrequencies as $term => $frequency) {
            $termFrequencies[$term] = ($frequency / $total) * ($idf[$term] ?? 0.0);
        }

        return $termFrequencies;
    }

    /** @param array<string, float> $left
     * @param  array<string, float>  $right
     */
    private function cosine(array $left, array $right): float
    {
        if ($left === [] || $right === []) {
            return 0.0;
        }

        $dot = 0.0;
        foreach ($left as $term => $weight) {
            $dot += $weight * ($right[$term] ?? 0.0);
        }

        $leftNorm = sqrt(array_sum(array_map(static fn (float $weight): float => $weight ** 2, $left)));
        $rightNorm = sqrt(array_sum(array_map(static fn (float $weight): float => $weight ** 2, $right)));

        return $leftNorm === 0.0 || $rightNorm === 0.0 ? 0.0 : $dot / ($leftNorm * $rightNorm);
    }

    /** @param array<string, float> $sourceVector
     * @param  list<string>  $candidateTokens
     * @return list<string>
     */
    private function sharedTerms(array $sourceVector, array $candidateTokens): array
    {
        $sharedTerms = array_intersect(array_keys($sourceVector), array_unique($candidateTokens));
        usort($sharedTerms, fn (string $left, string $right): int => ($sourceVector[$right] ?? 0.0) <=> ($sourceVector[$left] ?? 0.0));

        return array_map(
            static fn (string $term): string => 'Shared term: '.$term,
            array_slice($sharedTerms, 0, 4),
        );
    }
}
