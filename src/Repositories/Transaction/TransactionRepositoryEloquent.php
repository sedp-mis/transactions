<?php

namespace SedpMis\Transactions\Repositories\Transaction;

use SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentApprovalInterface;
use SedpMis\Transactions\Interfaces\SignatoryDocumentTypesInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionInterface;
use SedpMis\Transactions\Interfaces\UserResolverInterface;
use SedpMis\BaseRepository\BaseBranchRepositoryEloquent;
use SedpMis\BaseRepository\RepositoryInterface;
use SedpMis\Transactions\Models\SignatorySet;

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
     * Constructor.
     *
     * @param \SedpMis\Transactions\Models\Interfaces\TransactionInterface $model
     * @param \SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface $signatory
     */
    public function __construct(
        TransactionInterface $model,
        UserResolverInterface $userResolver,
        SignatoryRepositoryInterface $signatory,
        DocumentApprovalInterface $documentApproval,
        TransactionApprovalInterface $transactionApproval,
        SignatoryDocumentTypesInterface $signatoryDocumentTypes
    ) {
        $this->model = $model;

        $this->signatory = $signatory;

        $this->userResolver = $userResolver;

        $this->documentApproval = $documentApproval;

        $this->transactionApproval = $transactionApproval;

        $this->signatoryDocumentTypes = $signatoryDocumentTypes;
    }

    /**
     * Queue a transaction for approval.
     *
     * @param  array|\SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    public function queue($transaction)
    {
        $transaction = is_array($transaction) ? $this->model->newInstance($transaction) : $transaction;

        // Set default attributes
        foreach ($this->queueDefaultAttributes() as $attrib => $value) {
            $transaction->{$attrib} = $transaction->{$attrib} ?: $value;
        }

        // init for reference
        $signatorySet = null;

        // Set current_signatory if not set
        if (empty($transaction->current_signatory)) {
            if (empty($transaction->transaction_menu_id)) {
                throw new \Exception('Attribute transaction_menu_id does not exists in the given transaction. Cannot find signatorySet and current_signatory');
            }

            $signatorySet                   = $this->findSignatorySet($transaction->transaction_menu_id);
            $transaction->current_signatory = $signatorySet->signatories->first()->id;
        }

        // Set current_user_signatory if not set
        if (empty($transaction->current_user_signatory)) {
            $signatorySet                        = $signatorySet ?: $this->findSignatorySet($transaction->transaction_menu_id);
            $transaction->current_user_signatory = $signatorySet->signatories->first()->user_id ?: $this->findUserByJob($signatorySet->signatories->first()->job_id)->id;
            $transaction->current_user_signatory = $this->userResolver->getUser($signatorySet->signatories->first())->id;
        }

        $this->save($transaction);

        return $transaction;
    }

    /**
     * Find signatory set of a menu.
     *
     * @param  int $menuId
     * @return \SignatorySet
     */
    public function findSignatorySet($menuId)
    {
        $signatorySet = SignatorySet::with([
            'signatories' => function ($query) {
                return $query->orderBy('hierarchy')->limit(1);
            },
        ])->whereHas('menus', function ($q) use ($menuId) {
            $q->where('menu_id', $menuId);
        })
        ->first();

        if (is_null($signatorySet)) {
            throw new \Exception("Error: transaction_signatory_set is not set for menu id {$menuId}");
        }

        if ($signatorySet->signatories->count() == 0) {
            throw new \Exception("SignatorySet \"{$signatorySet->name}\" (id: {$signatorySet->id}) has no signatories.");
        }

        return $signatorySet;
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
