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
        ])->find($transactionId);

        $approvals = $transaction->transactionApprovals;

        $signatories = collection();

        if ($transaction->currentSignatory) {
            $query = $this->signatory
                ->with([
                    'job',
                    'user',
                    'signatoryAction',
                ])
                ->where('signatory_set_id', $transaction->currentSignatory->signatory_set_id)
                ->orderBy('hierarchy');

            if ($approvals->count()) {
                $query->where('hierarchy', '>', $approvals->last()->hierarchy);
            }

            $signatories = $query->get();

            foreach ($signatories as $signatory) {
                $signatory->setRelation('user', $signatory->getUser());
                if (is_null($signatory->job_id) && $signatory->user_id) {
                    $signatory->setRelation('job', $signatory->user->job);

                    unset($signatory->user->job);
                }
            }
        }

        $approvals->addMany($signatories->all());

        return $approvals;
    }
}
