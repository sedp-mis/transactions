<?php

namespace SedpMis\Transactions\Repositories\Transaction;

class NotificationAssignedApprover
{
    public $transaction;

    public function make($transaction)
    {
        $this->transaction = $transaction;

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
        $approver = $this->transaction->transactionApprovals->sortByDesc('hierarchy')->first(function ($approval) {
            return $approval->status == 'A';
        });

        if ($approver) {
            return $approver;
        }

        return $transaction->transactedBy;
    }
}
