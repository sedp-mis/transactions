<?php

namespace SedpMis\Transactions\Repositories\Transaction;

use SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentApprovalInterface;
use SedpMis\Transactions\Interfaces\SignatoryDocumentTypesInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionInterface;
use SedpMis\Transactions\Interfaces\MenuSignatorySetInterface;
use SedpMis\Transactions\Interfaces\UserResolverInterface;
use SedpMis\BaseRepository\BaseBranchRepositoryEloquent;
use SedpMis\BaseRepository\RepositoryInterface;
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
     * User resolver.
     *
     * @var \SedpMis\Transactions\Interfaces\UserResolverInterface
     */
    protected $userResolver;

    /**
     * Signatory document types repository.
     *
     * @var \SedpMis\Transactions\Interfaces\SignatoryDocumentTypesInterface
     */
    protected $signatoryDocumentTypes;

    /**
     * DocumentApproval model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\DocumentApprovalInterface
     */
    protected $documentApproval;

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
     * @param UserResolverInterface           $userResolver
     * @param SignatoryRepositoryInterface    $signatory
     * @param DocumentApprovalInterface       $documentApproval
     * @param MenuSignatorySetInterface       $menuSignatorySet
     * @param TransactionApprovalInterface    $transactionApproval
     * @param SignatoryDocumentTypesInterface $signatoryDocumentTypes
     */
    public function __construct(
        TransactionInterface $model,
        UserResolverInterface $userResolver,
        SignatoryRepositoryInterface $signatory,
        DocumentApprovalInterface $documentApproval,
        MenuSignatorySetInterface $menuSignatorySet,
        TransactionApprovalInterface $transactionApproval,
        SignatoryDocumentTypesInterface $signatoryDocumentTypes
    ) {
        $this->model = $model;

        $this->signatory = $signatory;

        $this->userResolver = $userResolver;

        $this->menuSignatorySet = $menuSignatorySet;

        $this->documentApproval = $documentApproval;

        $this->transactionApproval = $transactionApproval;

        $this->signatoryDocumentTypes = $signatoryDocumentTypes;
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

        // Set relation curSignatory (fk: current_signatory) if not set
        if (empty($transaction->curSignatory)) {
            if (empty($transaction->transaction_menu_id)) {
                throw new InvalidArgumentException('Attribute transaction_menu_id does not exists in the given transaction. Cannot find signatorySet and current_signatory');
            }

            $signatory = $this->menuSignatorySet->findSignatorySet($transaction->transaction_menu_id)->signatories->first();
            $transaction->setRelation('curSignatory', $signatory);
        }

        // Set relation curUserSignatory (fk: current_user_signatory) if not set
        if (empty($transaction->curUserSignatory)) {
            $transaction->setRelation('curUserSignatory', $this->userResolver->getUser($transaction->curSignatory));
        }

        // Make sure to save fks
        $transaction->current_signatory      = $transaction->curSignatory->id;
        $transaction->current_user_signatory = $transaction->curUserSignatory->id;

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
            'transacted_by' => get_user_session(),
            'status'        => 'Q',
            'transacted_at' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Save the accounting document of a transaction.
     *
     * @param  int $transactionId  [description]
     * @param  array|\Modules\Transaction\Data\AccountingDocument $acctDoc
     * @param  int $documentTypeId
     * @return \Document
     */
    public function saveAccountingDocument($transactionId, $acctDoc, $documentTypeId = null)
    {
        // If there is no accounting document
        if (!$acctDoc) {
            return $acctDoc;
        }

        // For array compatibility polymorphism
        if (is_array($acctDoc)) {
            $acctDocs = $acctDoc;

            $documents = collection();
            foreach ($acctDocs as $acctDoc) {
                $documents[] = $this->saveAccountingDocument($transactionId, $acctDoc, $documentTypeId);
            }

            return $documents;
        }

        // Begin code logic
        $document = Document::create([
            'transaction_id'   => $transactionId,
            'document_type_id' => $documentTypeId ?: $acctDoc->documentTypeId,
        ]);

        $journalHead = JournalHead::create([
            'accounting_period_id' => AccountingPeriod::where('is_active', 1)->first()->id,
            'document_id'          => $document->id,
            'book_id'              => $acctDoc->bookId ?: get_book_session(),
            'particulars'          => $acctDoc->particulars,
        ]);

        // Save Payee
        if ($payee = $acctDoc->getPayee()) {
            $payee['journal_head_id'] = $journalHead->id;

            Payee::create($payee);
        }

        // Save Receipt
        if ($receipt = $acctDoc->getReceipt()) {
            Receipt::where('id', $receipt['id'])->update(['transaction_id' => $transactionId]);
        }

        // Save journal_head_id to check
        if ($checkId = $acctDoc->checkId) {
            $check                  = Check::where('id', $checkId)->first();
            $check->journal_head_id = $journalHead->id;
            $check->save();
        }

        $journalEntries = collection();
        foreach ($acctDoc->getEntries() as $entry) {
            $entry['journal_head_id'] = $journalHead->id;
            $journalEntries[]         = JournalEntry::create($entry);
        }

        $journalHead->setRelation('journalEntries', $journalEntries);
        $document->setRelation('journalHead', $journalHead);

        return $document;
    }

    /**
     * Create document approvals for documents of transaction.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function createDocumentApprovals($transaction, $signatory)
    {
        $documentTypeIds = $this->signatoryDocumentTypes->findDocumentTypes(
            $transaction->transaction_menu_id,
            $transaction->curSignatory->id
        )->pluck('id');

        $collection = collection();

        $unsavedDocs = $transaction->documents->filter(function ($doc) {
            return !$doc->exists;
        });

        $transaction->documents()->saveMany($unsavedDocs->all());

        foreach ($transaction->documents as $document) {
            if (in_array($document->document_type_id, $documentTypeIds)) {
                $collection[] = $this->documentApproval->firstOrCreate([
                    'document_id'         => $document->id,
                    'user_id'             => $this->userResolver->getUser($signatory)->id,
                    'job_id'              => $this->userResolver->getUser($signatory)->job_id,
                    'signatory_action_id' => $signatory->signatoryAction->id,
                ]);
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
            'user_id'             => $this->userResolver->getUser($signatory)->id,
            'job_id'              => $this->userResolver->getUser($signatory)->job_id,
            'signatory_action_id' => $signatory->signatoryAction->id,
            'status'              => $action,
            'remarks'             => $remarks,
        ]);
    }

    /**
     * Accept a transaction by the curSignatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $remarks
     * @return void
     */
    public function accept($transaction, $signatory = null, $remarks = '')
    {
        $signatory = $signatory ?: $transaction->curSignatory;

        $nextSignatory = $this->signatory->nextSignatory($signatory->id);

        if ($nextSignatory) {
            $transaction->current_user_signatory = $this->userResolver->getUser($nextSignatory)->id;
            $transaction->current_signatory      = $nextSignatory->id;
            $transaction->status                 = 'Q';
            $transaction->save();
        } else {
            $transaction->status = 'A';
        }

        $transaction->save();

        $this->createTransactionApproval($transaction, $signatory, 'A', $remarks);
        $this->createDocumentApprovals($transaction, $signatory);

        // Fire event for final approved of transaction
        if ($transaction->status === 'A') {
            Event::fire("transaction_approval.{$transaction->transaction_menu_id}.approved", [$transaction]);
        }
    }

    /**
     * Reject a transaction by the curSignatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $remarks
     * @return void
     */
    public function reject($transaction, $signatory = null, $remarks = '')
    {
        $signatory = $signatory ?: $transaction->curSignatory;

        $transaction->current_signatory      = $signatory->id;
        $transaction->current_user_signatory = $this->userResolver->getUser($signatory)->id;
        $transaction->status                 = 'R';
        $transaction->save();

        $this->createTransactionApproval($transaction, $signatory, 'R', $remarks);

        // Fire event for rejected of transaction
        Event::fire("transaction_approval.{$transaction->transaction_menu_id}.rejected", [$transaction]);
    }

    /**
     * Hold a transaction by the curSignatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @param  string $remarks
     * @return void
     */
    public function hold($transaction, $signatory = null, $remarks = '')
    {
        $signatory = $signatory ?: $transaction->curSignatory;

        $transaction->status = 'Q';
        if ($transaction->current_user_signatory != $this->userResolver->getUser($signatory)->id) {
            $transaction->current_user_signatory = $this->userResolver->getUser($signatory)->id;
            $transaction->current_signatory      = $signatory->id;
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
}
