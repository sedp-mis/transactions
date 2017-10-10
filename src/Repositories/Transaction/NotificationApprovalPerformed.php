<?php

namespace SedpMis\Transactions\Repositories\Transaction;

use SedpMis\Notifications\NotificationInterface;

class NotificationApprovalPerformed implements NotificationInterface
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

        $currentUser = $transaction->getCurrentApproval()->user;

        return [
            'title' => "<strong>{$currentUser->full_name_2}</strong> {$this->actionPerformed()} your endorsed transaction for <strong>{$menuName}</strong>.",
        ];
    }

    private function actionPerformed()
    {
        return $this->transaction->getCurrentApproval()->status == 'A' ? 'accepted' : 'rejected';
    }

    public function receivers()
    {
        return $this->transaction->getPreviousApproval() && $this->transaction->getPreviousApproval()->user ?
            collection([$this->transaction->getPreviousApproval()->user]) :
            collection([$this->transaction->transactedBy]);
    }
}
