<?php

namespace SedpMis\Transactions\Models;

class JournalHead extends \Eloquent
{
    protected $fillable = ['accounting_period_id', 'document_id', 'book_id', 'particulars', 'is_void'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }
}
