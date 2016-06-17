<?php

namespace SedpMis\Transactions\Models;

use Services\ModelScope\AccountScopeTrait;
use Services\ModelScope\BookScopeTrait;
use Services\ModelScope\DocumentScopeTrait;
use Services\ModelScope\JournalHeadScopeTrait;
use Services\ModelScope\TransactionScopeTrait;
//use Services\ModelScope\BranchAssignmentScopeTrait;

class JournalEntry extends \BaseModel {

    use TransactionScopeTrait, DocumentScopeTrait, AccountScopeTrait, JournalHeadScopeTrait, BookScopeTrait;

    protected $fillable = ['journal_head_id', 'account_id', 'debit', 'credit', 'balance', 'is_contra'];

    protected $typecasts = [
        'debit' => 'float, *',
        'credit' => 'float, *',
    ];

    public function scopeWithAccount($query)
    {
        $query->addSelect([
            'accounts.title as acc_title',
            'accounts.code as acc_code'
        ]);

        $query->leftJoin('accounts', 'accounts.id', '=', 'journal_entries.account_id');
    }

    public function scopeWithEntries($query)
    {
        $query->join('journal_heads', 'journal_heads.id', '=', 'journal_entries.journal_head_id')
              ->join('documents', 'documents.id', '=', 'journal_heads.document_id')
              ->join('transactions', 'transactions.id', '=', 'documents.transaction_id')
              ->join('accounts', 'accounts.id', '=', 'journal_entries.account_id')
              ->join('books', 'books.id', '=', 'journal_heads.book_id');
    }
}