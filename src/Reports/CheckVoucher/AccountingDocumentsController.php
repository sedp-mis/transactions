<?php

namespace SedpMis\Transactions\Reports\AccountingDocuments;

use SedpMis\Transactions\Reports\Documents\DocumentsController;

class AccountingDocumentsController extends DocumentsController
{
    /**
     * Document type codes for this.
     *
     * @var array
     */
    protected $documentTypeIds = ['ACC'];

    protected $journalHead;

    public function __construct($document, $menuSignatory, $transaction, $journalHead)
    {
       $this->document      = $document;
       $this->menuSignatory = $menuSignatory;
       $this->transaction   = $transaction;
       $this->journalHead   = $journalHead;
    }

    public function loadReportPdf($transaction)
    {
        $journalHeads = $this->journalHead->findByDocumentIds($transaction->documents->pluck('id'));
    }

}