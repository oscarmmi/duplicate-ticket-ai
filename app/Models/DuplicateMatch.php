<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateMatch extends Model
{
    protected $fillable = [
        'duplicate_check_id',
        'matched_ticket_id',
        'score',
        'llm_confirmed',
        'reasons',
    ];

    /** @return BelongsTo<DuplicateCheck, $this> */
    public function duplicateCheck(): BelongsTo
    {
        return $this->belongsTo(DuplicateCheck::class);
    }

    /** @return BelongsTo<SupportTicket, $this> */
    public function matchedTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'matched_ticket_id');
    }

    /** @return array{reasons: 'array', llm_confirmed: 'boolean', score: 'float'} */
    protected function casts(): array
    {
        return [
            'reasons' => 'array',
            'llm_confirmed' => 'boolean',
            'score' => 'float',
        ];
    }
}
