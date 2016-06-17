<?php

namespace SedpMis\Transactions\Reports\Documents;

use SedpMis\Transactions\Helper\DocumentSignatoryHelper;

abstract class DocumentsController extends \Illuminate\Routing\Controller
{
    /**
     * Accounting document repository.
     *
     * @var [type]
     */
    protected $document;

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
     * Document type categories for the report
     *
     * @var array
     */
    protected $documentTypeIds = [];


    public function __construct($document, $documentTypeSignatory, $transaction)
    {
       $this->document      = $document; 
       $this->documentTypeSignatory = $documentTypeSignatory;
       $this->transaction   = $transaction;
    }

    public function show($transactionId)
    {
        $transaction = $this->transaction->withDocumentTypes($this->documentTypeIds())
            ->with([
                'menu',
                'documents.documentApprovals.signatoryAction',
                'documents.documentApprovals.user', 
                'transactionApprovals'
            ])
            ->find($transactionId);

        $signatories = $this->documentTypeSignatory->findSignatoriesByTransaction(
            $transaction, 
            $transaction->documents->pluck('documentType'),
            $transaction->transactionApprovals->count() + 1
        );

        $transaction->documents = DocumentSignatoryHelper::setDocumentSignatories($transaction->documents, $signatories);

        $this->loadReport($transaction);
    }

    public function documentTypeIds()
    {
        return $this->documentTypeIds;
    }

    abstract public function loadReportPdf($transaction);
}
