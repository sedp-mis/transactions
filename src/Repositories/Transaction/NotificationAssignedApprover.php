<?php

namespace SedpMis\Transactions\Repositories\Transaction;

use SedpMis\Notifications\NotificationInterface;

class NotificationAssignedApprover implements NotificationInterface
{
    public $transaction;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function make()
    {
        $transaction = $this->transaction;

        $menuName = $transaction->menu->transaction_name ?: $transaction->menu->name;

        $from = $this->getFromUser();

        if ($from) {
            return [
                'title' => "<b>{$from->full_name_2}</b> endorsed a transaction <b>{$menuName}</b> for your approval.",
            ];
        }

        return [
            'title' => "You have a pending approval for <b>{$menuName}</b>.",
        ];
    }

    public function getFromUser()
    {
        if ($this->transaction->getPreviousApproval() && $this->transaction->getPreviousApproval()->user) {
            return $this->transaction->getPreviousApproval()->user;
        }

        return $this->transaction->transactedBy;
    }

    public function receivers()
    {
        return collection([$this->transaction->getCurrentApproval()->user]);
    }
}
