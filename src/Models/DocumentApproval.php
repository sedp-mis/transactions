<?php

namespace SedpMis\Transactions\Models;

class DocumentApproval extends DocumentSignatory
{
    protected $fillable = ['id', 'user_id', 'document_id', 'signatory_action_id'];

    // protected $attributes = [
    //     'is_actual' => 1,
    // ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function signatoryAction()
    {
        return $this->belongsTo(SignatoryAction::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
