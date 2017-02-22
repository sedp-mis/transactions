<?php

namespace SedpMis\Transactions\Repositories\Transaction;

use SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentSignatoryInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionInterface;
use SedpMis\Transactions\Interfaces\MenuSignatorySetInterface;
use SedpMis\BaseRepository\BaseBranchRepositoryEloquent;
use SedpMis\BaseRepository\RepositoryInterface;
use SedpMis\Transactions\EventHandlersListener;
use SedpMis\Transactions\Models\SignatorySet;
use Illuminate\Support\Facades\Event;
use RuntimeException;

class TransactionRepositoryEloquent extends BaseBranchRepositoryEloquent implements RepositoryInterface, TransactionRepositoryInterface
{
    /**
     * Signatory repository.
     *
     * @var \SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface
     */
    protected $signatory;

    /**
     * TransactionApproval model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface
     */
    protected $transactionApproval;

    /**
     * DocumentSignatory model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\DocumentSignatoryInterface
     */
    protected $documentSignatory;

    /**
     * Menu's signatorySet repository.
     *
     * @var \SedpMis\Transactions\Interfaces\MenuSignatorySetInterface
     */
    protected $menuSignatorySet;

    /**
     * Construct.
     *
     * @param TransactionInterface            $model
     * @param SignatoryRepositoryInterface    $signatory
     * @param DocumentSignatoryInterface      $documentSignatory
     * @param MenuSignatorySetInterface       $menuSignatorySet
     * @param EventHandlersListener           $eventHandlersListener
     * @param TransactionApprovalInterface    $transactionApproval
     */
    public function __construct(
        TransactionInterface $model,
        SignatoryRepositoryInterface $signatory,
        DocumentSignatoryInterface $documentSignatory,
        MenuSignatorySetInterface $menuSignatorySet,
        EventHandlersListener $eventHandlersListener,
        TransactionApprovalInterface $transactionApproval
    ) {
        $this->model = $model;

        $this->signatory = $signatory;

        $this->menuSignatorySet = $menuSignatorySet;

        $this->documentSignatory = $documentSignatory;

        $this->transactionApproval = $transactionApproval;

        $eventHandlersListener->listen();
    }

    /**
     * Queue a transaction for approval.
     *
     * @param  array|\SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  int|\SedpMis\Transactions\Models\Interfaces\SignatorySetInterface $signatorySet
     * @throws \InvalidArgumentException
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    public function queue($transaction, $signatorySet = null)
    {
        $transaction  = is_array($transaction) ? $this->model->newInstance($transaction) : $transaction;
        $signatorySet = $signatorySet ?: $this->getSignatorySet($transaction);

        // Set default attributes
        foreach ($this->queueDefaultAttributes() as $attrib => $value) {
            $transaction->{$attrib} = $transaction->{$attrib} ?: $value;
        }

        $signatories = is_numeric($signatorySet) ? $this->signatory->findSignatoriesOfSignatorySet($signatorySet) : $signatorySet->signatories;

        // Set current user signatory
        $transaction->currentUser()->associate($signatories->first()->getUser());
        $transaction->currentSignatory()->associate($signatories->first());

        $this->save($transaction);

        $approvals = $this->createTransactionApprovals($transaction, $signatories);

        $transaction->setRelation('transactionApprovals', $approvals);

        return $transaction;
    }

    /**
     * Get the signatory set of transaction.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @return \SedpMis\Transactions\Models\Interfaces\SignatorySetInterface
     */
    protected function getSignatorySet($transaction)
    {
        if (empty($transaction->menu_id)) {
            throw new RuntimeException('Attribute menu_id does not exists in the given transaction. Cannot find signatorySet and signatories.');
        }

        // Get the default reversal signatory set for reversal transaction.
        if ($transaction->is_reversal) {
            return $this->signatory->defaultReversalSignatorySet();
        }

        return $this->menuSignatorySet->findSignatorySet($transaction->menu_id);
    }

    /**
     * Create transaction approvals.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \Illuminate\Database\Eloquent\Collection $signatories
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function createTransactionApprovals($transaction, $signatories)
    {
        $approvals = collection();
        foreach ($signatories as $signatory) {
            $approvals[] = $this->transactionApproval->create([
                'transaction_id'      => $transaction->id,
                'signatory_id'        => $signatory->id,
                'user_id'             => $signatory->getUser()->id,
                'job_id'              => $signatory->getUser()->job_id,
                'signatory_action_id' => $signatory->signatoryAction->id,
                'hierarchy'           => $signatory->hierarchy,
            ]);
        }

        return $approvals;
    }

    /**
     * Return default attributes for transaction for queue.
     *
     * @return array
     */
    protected function queueDefaultAttributes()
    {
        return [
            'branch_id'             => get_branch_session(),
            'transacted_by_user_id' => get_user_session(),
            'status'                => 'Q',
        ];
    }

    /**
     * Sign document signatories.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $documents
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @return void
     */
    protected function signDocumentSignatories($documents, $approval)
    {
        foreach ($documents as $document) {
            $documentSignatories = $document->documentSignatories->filter(function ($documentSignatory) use ($approval) {
                return $approval->signatory_id == $documentSignatory->signatory_id;
            });

            foreach ($documentSignatories as $documentSignatory) {
                $documentSignatory->is_signed = 1;
                $documentSignatory->save(); // subject for optimization, use single update query
            }
        }
    }

    /**
     * Perform transaction approval by the approver (approval).
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $action
     * @param  string $remarks
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface
     */
    protected function performTransactionApproval($approval, $action, $remarks)
    {
        $approval->status       = $action;
        $approval->remarks      = $remarks;
        $approval->performed_at = date('Y-m-d H:i:s');
        $approval->save();

        return $approval;
    }

    /**
     * Accept a transaction by the currentSignatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $remarks
     * @return void
     */
    public function accept($transaction, $approval = null, $remarks = '')
    {
        $approval = $approval ?: $transaction->getCurrentApproval();

        $nextApproval = $transaction->getNextApproval($approval);

        if ($nextApproval) {
            $transaction->status = 'Q';
            // Set next approver user signatory
            $transaction->currentUser()->associate($nextApproval->user);
            $transaction->currentSignatory()->associate($nextApproval->signatory);
        } else {
            $transaction->status        = 'A';
            $transaction->approved_at   = date('Y-m-d H:i:s');
            $transaction->transacted_at = $transaction->transacted_at ?: $transaction->approved_at;
        }

        $transaction->save();

        $this->performTransactionApproval($approval, 'A', $remarks);
        $this->signDocumentSignatories($transaction->documents, $approval);

        // Fire event for final approved of transaction
        if ($transaction->status === 'A') {
            // Reverse reference transaction by rejecting it.
            if ($transaction->is_reversal && $transaction->referenceTransaction) {
                $referenceTransaction = $transaction->referenceTransaction;

                $referenceTransaction->status                = 'R';
                $referenceTransaction->rev_by_transaction_id = $transaction->id;
                $referenceTransaction->rejected_at           = date('Y-m-d H:i:s');
                $referenceTransaction->save();
            }

            Event::fire("transaction_approval.{$transaction->menu_id}.approved", [$transaction]);
        }
    }

    /**
     * Reject a transaction by the currentApproval.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $remarks
     * @return void
     */
    public function reject($transaction, $approval = null, $remarks = '')
    {
        $approval = $approval ?: $transaction->getCurrentSignatory();

        $transaction->status      = 'R';
        $transaction->rejected_at = date('Y-m-d H:i:s');
        $transaction->save();

        $this->performTransactionApproval($approval, 'R', $remarks);

        // DO NOTHING ON THE REFERENCE TRANSACTION
        // Unhold reference transaction, bringing it back to queue.
        // if ($transaction->is_reversal && $transaction->referenceTransaction) {
        //     $referenceTransaction = $transaction->referenceTransaction;

        //     $referenceTransaction->status = 'Q';
        //     $referenceTransaction->save();
        // }

        // Fire event for rejected of transaction
        Event::fire("transaction_approval.{$transaction->menu_id}.rejected", [$transaction]);
    }

    /**
     * Hold a transaction by the currentSignatory.
     * Be aware that a transaction can be hold by the system (not by signatory) in case there is a
     * transaction reversal for that transaction. In that case only status is set to H and has no record
     * in transaction approvals. The reference for that kind of transaction
     * is via the ref_transaction_id (reversalTransactions relation, referenceTransaction is the inverse of relation).
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $remarks
     * @return void
     */
    public function hold($transaction, $approval = null, $remarks = '')
    {
        $approval = $approval ?: $transaction->getCurrentApproval();

        $transaction->status = 'Q';

        $transaction->save();

        $this->performTransactionApproval($approval, 'H', $remarks);
    }

    /**
     * Eager load documents with specific document type ids.
     *
     * @param  array  $documentTypeIds
     * @return $this
     */
    public function withDocumentTypes(array $documentTypeIds)
    {
        $this->with([
            'documents.documentType' => function ($query) use ($documentTypeIds) {
                $query->whereIn('id', $documentTypeIds);
            },
        ]);

        return $this;
    }

    /**
     * Get the tracked transactions for the involved user.
     *
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrackedTransactions($userId)
    {
        $query = $this->prepareQuery();
        $query->where('transactions.status', 'Q');
        $query->whereIn('transactions.id', $this->transactionApproval->where('user_id', $userId)->lists('transaction_id') ?: [null]);

        return $query->get($this->finalAttributes());
    }

    /**
     * Get user's historical transactions.
     *
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistoricalTransactions($userId)
    {
        $query = $this->prepareQuery();
        $query->whereIn('transactions.id', $this->transactionApproval->where('user_id', $userId)->lists('transaction_id') ?: [null]);

        return $query->get($this->finalAttributes());
    }
}
