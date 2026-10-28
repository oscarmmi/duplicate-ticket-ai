<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    protected $fillable = ['subject', 'body', 'status'];

    /** @return HasMany<DuplicateCheck, $this> */
    public function duplicateChecks(): HasMany
    {
        return $this->hasMany(DuplicateCheck::class, 'ticket_id');
    }

    /** @param Builder<SupportTicket> $query */
    public function scopeOpen(Builder $query): void
    {
        $query->where('status', 'open');
    }
}
