<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Models\Interfaces\TransactionInterface;

class TransactionApprovalsController extends \Illuminate\Routing\Controller
{
    /**
     * Transaction model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    protected $transaction;

    /**
     * Construct.
     *
     * @param TransactionInterface $transaction
     */
    public function __construct(TransactionInterface $transaction)
    {
        $this->transaction = $transaction;
    }

    /**
     * Return the signatories of a transaction.
     *
     * @param  int $id
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index($transactionId)
    {
        $transaction = $this->transaction->with([
            'transactionApprovals.signatory',
            'transactionApprovals.user',
            'transactionApprovals.job',
            'transactionApprovals.signatoryAction',
        ])->findOrFail($transactionId);

        return $transaction->transactionApprovals;
    }
}
