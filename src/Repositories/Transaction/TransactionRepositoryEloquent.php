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
use InvalidArgumentException;

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
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     *
     * @throws \InvalidArgumentException
     */
    public function queue($transaction)
    {
        $transaction = is_array($transaction) ? $this->model->newInstance($transaction) : $transaction;

        // Set default attributes
        foreach ($this->queueDefaultAttributes() as $attrib => $value) {
            $transaction->{$attrib} = $transaction->{$attrib} ?: $value;
        }

        // Set the default reversal signatory set for reversal transaction.
        if ($transaction->is_reversal && empty($transaction->currentSignatory)) {
            $signatory = $this->signatory->defaultReversalSignatorySet()->signatories->first();

            $transaction->setRelation('currentSignatory', $signatory);
        }

        // Set relation currentSignatory (fk: current_signatory_id) if not set
        if (empty($transaction->currentSignatory)) {
            if (empty($transaction->menu_id)) {
                throw new InvalidArgumentException('Attribute menu_id does not exists in the given transaction. Cannot find signatorySet and currentSignatory');
            }

            $signatory = $this->menuSignatorySet->findSignatorySet($transaction->menu_id)->signatories->first();
            $transaction->setRelation('currentSignatory', $signatory);
        }

        // Set relation currentUser (fk: current_user_id) if not set
        if (empty($transaction->currentUser)) {
            $transaction->setRelation('currentUser', $signatory->getUser());
        }

        // Make sure to save fks
        $transaction->current_signatory_id = $transaction->currentSignatory->id;
        $transaction->current_user_id      = $transaction->currentUser->id;

        $this->save($transaction);

        return $transaction;
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
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @return void
     */
    protected function signDocumentSignatories($documents, $signatory)
    {
        foreach ($transaction->documents as $document) {
            $documentSignatories = $document->documentSignatories->filter(function ($documentSignatory) use ($signatory) {
                return $signatory->id == $documentSignatory->signatory_id;
            });

            foreach ($documentSignatories as $documentSignatory) {
                $documentSignatory->is_signed = 1;
                $documentSignatory->save(); // subject for optimization, use single update query
            }
        }

        return $collection;
    }

    /**
     * Create transaction approvals.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $action
     * @param  string $remarks
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface
     */
    protected function createTransactionApproval($transaction, $signatory, $action, $remarks)
    {
        return $this->transactionApproval->create([
            'transaction_id'      => $transaction->id,
            'signatory_id'        => $signatory->id,
            'user_id'             => $signatory->getUser()->id,
            'job_id'              => $signatory->getUser()->job_id,
            'signatory_action_id' => $signatory->signatoryAction->id,
            'status'              => $action,
            'remarks'             => $remarks,
        ]);
    }

    /**
     * Accept a transaction by the currentSignatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $remarks
     * @return void
     */
    public function accept($transaction, $signatory = null, $remarks = '')
    {
        $signatory = $signatory ?: $transaction->currentSignatory;

        $nextSignatory = $this->signatory->nextSignatory($signatory->id);

        if ($nextSignatory) {
            $transaction->current_user_id      = $nextSignatory->getUser()->id;
            $transaction->current_signatory_id = $nextSignatory->id;
            $transaction->status               = 'Q';
            $transaction->save();
        } else {
            $transaction->status      = 'A';
            $transaction->approved_at = date('Y-m-d H:i:s');
        }

        $transaction->save();

        // TODO: move this in a better place
        // $unsavedDocs = $transaction->documents->filter(function ($doc) {
        //     return !$doc->exists;
        // });
        // $transaction->documents()->saveMany($unsavedDocs->all());

        $this->createTransactionApproval($transaction, $signatory, 'A', $remarks);
        $this->signDocumentSignatories($transaction->documents, $signatory);

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
     * Reject a transaction by the currentSignatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $remarks
     * @return void
     */
    public function reject($transaction, $signatory = null, $remarks = '')
    {
        $signatory = $signatory ?: $transaction->currentSignatory;

        $transaction->current_signatory_id = $signatory->id;
        $transaction->current_user_id      = $signatory->getUser()->id;
        $transaction->status               = 'R';
        $transaction->rejected_at          = date('Y-m-d H:i:s');
        $transaction->save();

        $this->createTransactionApproval($transaction, $signatory, 'R', $remarks);

        // Unhold reference transaction, bringing it back to queue.
        if ($transaction->is_reversal && $transaction->referenceTransaction) {
            $referenceTransaction = $transaction->referenceTransaction;

            $referenceTransaction->status = 'Q';
            $referenceTransaction->save();
        }

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
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $remarks
     * @return void
     */
    public function hold($transaction, $signatory = null, $remarks = '')
    {
        $signatory = $signatory ?: $transaction->currentSignatory;

        $transaction->status = 'Q';
        if ($transaction->current_user_id != $signatory->getUser()->id) {
            $transaction->current_user_id      = $signatory->getUser()->id;
            $transaction->current_signatory_id = $signatory->id;
        }

        $transaction->save();

        $this->createTransactionApproval($transaction, $signatory, 'H', $remarks);
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
