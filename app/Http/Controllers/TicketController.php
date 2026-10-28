<?php

namespace App\Http\Controllers;

use App\Models\DuplicateCheck;
use App\Models\SupportTicket;
use App\Services\Tickets\DuplicateFinder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TicketController extends Controller
{
    public function index(Request $request): Response
    {
        $checkId = $request->integer('check');
        $check = $checkId > 0
            ? DuplicateCheck::query()->with(['ticket', 'matches.matchedTicket'])->find($checkId)
            : null;

        return $this->renderPage($check);
    }

    public function findDuplicates(SupportTicket $ticket, DuplicateFinder $finder): Response
    {
        return $this->renderPage($finder->find($ticket));
    }

    private function renderPage(?DuplicateCheck $check): Response
    {
        $check?->loadMissing(['ticket', 'matches.matchedTicket']);

        return Inertia::render('Tickets/Index', [
            'tickets' => SupportTicket::query()
                ->orderByRaw("CASE status WHEN 'open' THEN 0 ELSE 1 END")
                ->orderByDesc('updated_at')
                ->get(['id', 'subject', 'body', 'status', 'created_at'])
                ->map(fn (SupportTicket $ticket): array => [
                    'id' => $ticket->id,
                    'subject' => $ticket->subject,
                    'body' => $ticket->body,
                    'status' => $ticket->status,
                    'createdAt' => $ticket->created_at?->toIso8601String(),
                ]),
            'stats' => [
                'total' => SupportTicket::query()->count(),
                'open' => SupportTicket::query()->where('status', 'open')->count(),
                'checks' => DuplicateCheck::query()->count(),
            ],
            'selectedCheck' => $check === null ? null : [
                'id' => $check->id,
                'status' => $check->status,
                'ticketId' => $check->ticket_id,
                'ticketSubject' => $check->ticket->subject,
                'matches' => $check->matches->map(fn ($match): array => [
                    'id' => $match->id,
                    'ticketId' => $match->matched_ticket_id,
                    'subject' => $match->matchedTicket->subject,
                    'body' => $match->matchedTicket->body,
                    'status' => $match->matchedTicket->status,
                    'score' => $match->score,
                    'llmConfirmed' => $match->llm_confirmed,
                    'reasons' => $match->reasons,
                ])->all(),
            ],
        ]);
    }
}
