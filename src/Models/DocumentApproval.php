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
        return $this->belongsTo('User');
    }

    public function signatoryAction()
    {
        return $this->belongsTo('SignatoryAction');
    }
}
