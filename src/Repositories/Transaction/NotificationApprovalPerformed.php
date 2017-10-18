<?php

namespace SedpMis\Transactions\Repositories\Transaction;

use SedpMis\Notifications\NotificationInterface;

class NotificationApprovalPerformed implements NotificationInterface
{
    public $transaction;
    public $approval;

    public function __construct($transaction, $approval)
    {
        $this->transaction = $transaction;
        $this->approval    = $approval;
    }

    public function make()
    {
        $transaction = $this->transaction;

        $menuName = $transaction->menu->transaction_name ?: $transaction->menu->name;

        return [
            'title' => "<strong>{$this->approval->user->full_name_2}</strong> {$this->actionPerformed()} your endorsed transaction for <strong>{$menuName}</strong> ({$transaction->remarks}).",
        ];
    }

    private function actionPerformed()
    {
        return $this->approval->status == 'A' ? 'accepted' : 'rejected';
    }

    public function receivers()
    {
        $users = collection([$this->transaction->transactedBy]);

        $approvals = $this->transaction->getTransactionApprovals()->filter(function ($approval) {
            return !empty($approval->status) && $approval->id != $this->approval->id;
        });

        return $users->addMany($approvals->map(function ($approval) {
            return $approval->user;
        }));
    }
}
