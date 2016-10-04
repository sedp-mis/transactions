<?php

namespace SedpMis\Transactions\Models;

class JournalEntry extends \Eloquent
{
    protected $fillable = ['journal_head_id', 'account_id', 'debit', 'credit', 'balance', 'is_contra'];

    protected $typecasts = [
        'debit'  => 'float, *',
        'credit' => 'float, *',
    ];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
