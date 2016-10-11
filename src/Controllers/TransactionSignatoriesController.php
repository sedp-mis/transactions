<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Models\Interfaces\TransactionInterface;
use SedpMis\Transactions\Models\Interfaces\SignatoryInterface;
use SedpMis\Transactions\Interfaces\UserResolverInterface;

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
     * User signatory resolver.
     *
     * @var \SedpMis\Transactions\Interfaces\UserResolverInterface
     */
    protected $userResolver;

    /**
     * Construct.
     *
     * @param TransactionInterface $transaction
     * @param SignatoryInterface   $signatory
     */
    public function __construct(TransactionInterface $transaction, SignatoryInterface $signatory, UserResolverInterface $userResolver)
    {
        $this->transaction  = $transaction;
        $this->signatory    = $signatory;
        $this->userResolver = $userResolver;
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
                $signatory->setRelation('user', $this->userResolver->getUser($signatory));
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
