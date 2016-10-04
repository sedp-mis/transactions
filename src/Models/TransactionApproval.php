<?php

namespace SedpMis\Transactions\Models;

class TransactionApproval extends \Eloquent
{
    protected $fillable = ['transaction_id', 'user_id', 'signatory_action_id', 'status', 'remarks'];

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
