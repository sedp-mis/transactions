<?php

namespace SedpMis\Transactions\Models;

class Branch extends \Eloquent
{
    protected $fillable = ['transaction_id', 'prepared_by', 'document_type_id', 'branch_id', 'is_archived', 'file_path'];

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
