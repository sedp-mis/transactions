<?php

namespace SedpMis\Transactions\Reports\AccountingDocuments;

use SedpMis\Transactions\Reports\Documents\DocumentsController;
use SedpMis\Transactions\Models\DocumentType;

class AccountingDocumentsController extends DocumentsController
{
    /**
     * Document type codes for this.
     *
     * @var array
     */
    protected $documentTypeIds = [DocumentType::JV, DocumentType::CD, DocumentType::OR_];

    protected $eagerLoadWithTransaction = [
        'documents.journalHead.journalEntries'
    ];

    public function loadReportPdf($transaction)
    {
        return die($transaction->toJson());
    }
}
