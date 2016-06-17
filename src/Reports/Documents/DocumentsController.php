<?php

namespace SedpMis\Transactions\Reports\Documents;

use SedpMis\Transactions\Repositories\DocumentTypeSignatory\DocumentTypeSignatoryRepositoryInterface;
use SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface;
use SedpMis\Transactions\Helpers\DocumentSignatoryHelper;

abstract class DocumentsController extends \Illuminate\Routing\Controller
{
    /**
     * Repository for documentType's signatories.
     *
     * @var
     */
    protected $documentTypeSignatory;

    /**
     * Transaction repository.
     *
     * @var [type]
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
            ->with([
                'menu',
                'documents.documentApprovals.signatoryAction',
                'documents.documentApprovals.user', 
                'transactionApprovals'
            ])
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
