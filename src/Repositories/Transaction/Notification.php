<?php

namespace SedpMis\Transactions\Repositories\Transaction;

class Notification
{
    public $transaction;

    public function make($transaction)
    {
        $this->transaction = $transaction;

        $menuName = $transaction->menu->transaction_name ?: $transaction->menu->name;

        $sender = $this->getSender();

        if ($sender) {
            return [
                'title' => "<b>{$sender->full_name_2}</b> endorsed a transaction <b>{$menuName}</b> for your approval.",
            ];
        }

        return [
            'title' => "You have a pending Approval for <b>{$menuName}</b>.",
        ];
    }

    public function getSender()
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
