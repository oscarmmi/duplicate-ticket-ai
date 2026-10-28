<?php

use App\Models\DuplicateCheck;
use App\Models\SupportTicket;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;
use App\Services\Llm\LlmConfirmation;
use App\Services\Llm\RetrievalOnlyLlmClient;
use App\Services\Tickets\DuplicateFinder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeTicket(string $subject, string $body, string $status = 'open'): SupportTicket
{
    return SupportTicket::query()->create(compact('subject', 'body', 'status'));
}

it('ranks the matching checkout tax ticket first and records a fake LLM confirmation', function () {
    $source = makeTicket(
        'Checkout tax charged twice on EU orders',
        'Checkout adds sales tax twice to European orders and customers see a duplicate tax line in the final total.',
    );
    $pair = makeTicket(
        'EU checkout applies sales tax two times',
        'The European checkout calculates sales tax twice and displays a duplicate tax amount on the customer order.',
    );
    makeTicket('Notification preferences will not save', 'Email settings revert after the customer saves notification preferences.');

    $fake = new FakeLlmClient;
    $this->app->instance(LlmClient::class, $fake);

    $check = app(DuplicateFinder::class)->find($source);
    $match = $check->matches()->orderByDesc('score')->firstOrFail();

    expect($match->matched_ticket_id)->toBe($pair->id)
        ->and($match->llm_confirmed)->toBeTrue()
        ->and($match->score)->toBeGreaterThanOrEqual(0.25)
        ->and($match->reasons)->toContain('The tickets describe the same customer issue and expected outcome.')
        ->and($fake->requests)->toHaveCount(1);
});

it('does not confirm a unique ticket even when the corpus is analyzed', function () {
    $source = makeTicket(
        'Exported report is missing the date column',
        'The monthly CSV export leaves the transaction date field blank although dates appear in the dashboard.',
    );
    makeTicket(
        'Notification preferences will not save',
        'Email settings revert after the customer saves notification preferences in account settings.',
    );

    $this->app->instance(LlmClient::class, new FakeLlmClient);

    $check = app(DuplicateFinder::class)->find($source);

    expect($check->matches)->toBeEmpty()
        ->and($check->status)->toBe('completed');
});

it('binds the LLM interface to the test fake when a test supplies it', function () {
    $fake = new FakeLlmClient;
    $this->app->instance(LlmClient::class, $fake);

    expect(app(LlmClient::class))->toBe($fake);
});

it('uses retrieval-only confirmation when no Gemini key is configured', function () {
    config(['services.gemini.api_key' => null]);

    expect(app(LlmClient::class))->toBeInstanceOf(RetrievalOnlyLlmClient::class);
});

it('seeds ten fictional Harbor Desk tickets', function () {
    $this->seed(DatabaseSeeder::class);

    expect(SupportTicket::query()->count())->toBe(10)
        ->and(SupportTicket::query()->where('status', 'open')->count())->toBe(9)
        ->and(SupportTicket::query()->where('status', 'closed')->count())->toBe(1);
});

it('lets a guest run duplicate detection and receives an Inertia page', function () {
    $source = makeTicket('Password reset link expires immediately', 'The reset email link is expired as soon as it is opened.');
    makeTicket('Password reset URL expires on arrival', 'The reset email URL is expired as soon as customers open it.');
    $this->app->instance(LlmClient::class, new FakeLlmClient);

    $this->post(route('tickets.duplicates', $source))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Tickets/Index')
            ->where('selectedCheck.ticketId', $source->id)
            ->has('selectedCheck.matches', 1));

    expect(DuplicateCheck::query()->where('ticket_id', $source->id)->count())->toBe(1);
});

it('keeps retrieval results when the optional LLM client throws', function () {
    $source = makeTicket('Mobile receipt upload crashes the app', 'The mobile app crashes while a receipt upload is processing.');
    $pair = makeTicket('Receipt upload crashes mobile app', 'The app crashes when processing a mobile receipt upload.');

    $this->app->instance(LlmClient::class, new class implements LlmClient
    {
        public function confirmDuplicate(string $source, string $candidate): LlmConfirmation
        {
            throw new RuntimeException('Simulated provider timeout.');
        }
    });

    $check = app(DuplicateFinder::class)->find($source);
    $match = $check->matches()->firstOrFail();

    expect($match->matched_ticket_id)->toBe($pair->id)
        ->and($match->llm_confirmed)->toBeFalse()
        ->and($match->reasons)->toContain('LLM confirmation was unavailable; lexical similarity only.');
});
