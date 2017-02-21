<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Models\Interfaces\TransactionInterface;
use SedpMis\Transactions\Models\Interfaces\SignatoryInterface;

class TransactionSignatoriesController extends \Illuminate\Routing\Controller
{
    /**
     * Transaction model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    protected $transaction;

    /**
     * Signatory model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\SignatoryInterface
     */
    protected $signatory;

    /**
     * Construct.
     *
     * @param TransactionInterface $transaction
     * @param SignatoryInterface   $signatory
     */
    public function __construct(TransactionInterface $transaction, SignatoryInterface $signatory)
    {
        $this->transaction = $transaction;
        $this->signatory   = $signatory;
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
            'currentSignatory',
        ])->findOrFail($transactionId);

        return $transaction->transactionApprovals;
    }
}
