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
            'curSignatory',
        ])->find($transactionId);

        $approvals = $transaction->transactionApprovals;

        $signatories = collection();

        if ($transaction->curSignatory) {
            $signatories = $this->signatory
                ->with([
                    'job',
                    'user',
                    'signatoryAction',
                ])
                ->where('signatory_set_id', $transaction->curSignatory->signatory_set_id)
                ->where('hierarchy', '>', $transaction->curSignatory->hierarchy)
                ->get();

            foreach ($signatories as $signatory) {
                $signatory->setRelation('user', $this->userResolver->getUser($signatory));
            }
        }

        $approvals->addMany($signatories->all());

        return $approvals;
    }
}
