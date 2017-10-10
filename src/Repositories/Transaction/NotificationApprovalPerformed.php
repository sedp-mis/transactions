<?php

namespace SedpMis\Transactions\Repositories\Transaction;

class NotificationApprovalPerformed
{
    public $transaction;

    public function make($transaction)
    {
        $this->transaction = $transaction;

        $menuName = $transaction->menu->transaction_name ?: $transaction->menu->name;

        $currentUser = $transaction->getCurrentApproval()->user;

        return [
            'title' => "<strong>{$currentUser->full_name_2}</strong> {$this->actionPerformed()} your endorsed transaction for <strong>{$menuName}</strong>.",
        ];
    }

    public function actionPerformed()
    {
        return $transaction->getCurrentApproval()->status == 'A' ? 'accepted' : 'rejected';
    }
}
