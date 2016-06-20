<?php

namespace SedpMis\Transactions\Reports\AccountingDocuments;

use SedpMis\Transactions\Reports\BaseDocument\BaseDocumentController;
use SedpMis\Transactions\Models\DocumentType;
use Reportings\AccountingDocument\PdfFactory;

class AccountingDocumentsController extends BaseDocumentController
{
    /**
     * Document type codes for this.
     *
     * @var array
     */
    protected $documentTypeIds = [DocumentType::JV, DocumentType::CD, DocumentType::OR_];

    protected $eagerLoadWithTransaction = [
        'documents.journalHead.journalEntries.account'
    ];

    public function loadReportPdf($transaction)
    {
        $pdf = (new PdfFactory)->make($transaction->documents->first()->documentType->id);

        $dataFormat = new DataFormatter($transaction);

        // Set pdf report data rows
        $pdf->setReportData($dataFormat->reportData());

        // Set pdf head details
        if ($transaction->documents->count() == 1) {
            $pdf->setOneHeaderTemplate(head($dataFormat->headDetails()));
        } else {
            $pdf->setHeaderTemplates($dataFormat->headDetails());
        }

        // // Set pdf signatories data
        // $pdf->setSignsData($dataFormat->signsData());
        
        $pdf->showReport();
    }
}
