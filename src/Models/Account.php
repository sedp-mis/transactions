<?php

namespace SedpMis\Transactions\Models;

class Account extends \Eloquent
{
    protected $typecasts = [
        'has_check'         => 'boolean, int',
        'is_multiple_check' => 'boolean, int',
    ];

    public function isCheckingAccount()
    {
        return $this->has_check == 1;
    }

    public function allowsMultipleCheck()
    {
        return $this->has_check && $this->is_multiple_check;
    }
}
