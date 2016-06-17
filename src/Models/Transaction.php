<?php

namespace SedpMis\Transactions\Models;

class Transaction extends \Eloquent
{

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'transaction_menu_id');
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function curSignatory()
    {
        return $this->belongsTo(Signatory::class, 'current_signatory');
    }

    public function curUserSignatory()
    {
        return $this->belongsTo(User::class, 'current_user_signatory');
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