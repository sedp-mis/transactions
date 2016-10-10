<?php

namespace SedpMis\Transactions\Models;

class Transaction extends \Eloquent
{
    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function currentSignatory()
    {
        return $this->belongsTo(Signatory::class);
    }

    public function currentUser()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionApprovals()
    {
        return $this->hasMany(TransactionApproval::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
