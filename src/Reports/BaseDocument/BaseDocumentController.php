<?php

namespace SedpMis\Transactions\Reports\BaseDocument;

use SedpMis\Transactions\Repositories\DocumentTypeSignatory\DocumentTypeSignatoryRepositoryInterface;
use SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface;
use SedpMis\Transactions\Helpers\DocumentSignatoryHelper;

abstract class BaseDocumentController extends \Illuminate\Routing\Controller
{
    /**
     * Repository for documentType's signatories.
     *
     * @var \SedpMis\Transactions\Repositories\DocumentTypeSignatory\DocumentTypeSignatoryRepositoryInterface
     */
    protected $documentTypeSignatory;

    /**
     * Transaction repository.
     *
     * @var \SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface
     */
    protected $transaction;

    /**
     * Document type Ids for the report
     *
     * @var array
     */
    protected $documentTypeIds = [];

    /**
     * Eagerload relations with transaction.
     *
     * @var array
     */
    protected $eagerLoadWithTransaction = [];


    public function __construct(
        DocumentTypeSignatoryRepositoryInterface $documentTypeSignatory,
        TransactionRepositoryInterface $transaction
    ) {
       $this->documentTypeSignatory = $documentTypeSignatory;
       $this->transaction           = $transaction;
    }

    public function show($transactionId)
    {
        $transaction = $this->transaction->withDocumentTypes($this->documentTypeIds)
            ->with(array_merge([
                'menu',
                'documents.documentApprovals.signatoryAction',
                'documents.documentApprovals.user', 
                'transactionApprovals'
            ], $this->eagerLoadWithTransaction))
            ->find($transactionId);

        $signatories = $this->documentTypeSignatory->findSignatoriesByTransaction(
            $transaction, 
            collection($transaction->documents->pluck('documentType')),
            $transaction->transactionApprovals->count() + 1
        );

        $transaction->documents = DocumentSignatoryHelper::setDocumentSignatories($transaction->documents, $signatories);

        $this->loadReportPdf($transaction);
    }

    abstract public function loadReportPdf($transaction);
}
