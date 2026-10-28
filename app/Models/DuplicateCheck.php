<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DuplicateCheck extends Model
{
    protected $fillable = ['ticket_id', 'status'];

    /** @return BelongsTo<SupportTicket, $this> */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /** @return HasMany<DuplicateMatch, $this> */
    public function matches(): HasMany
    {
        return $this->hasMany(DuplicateMatch::class, 'duplicate_check_id');
    }
}
