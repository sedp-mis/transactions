<?php

namespace SedpMis\Transactions\Models;

class Document extends \Eloquent
{
    protected $fillable = ['transaction_id', 'prepared_by', 'document_type_id', 'branch_id', 'is_archived', 'file_path'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function journalHead()
    {
        return $this->hasOne(JournalHead::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function documentSignatories()
    {
        return $this->hasMany(DocumentSignatory::class);
    }
}
