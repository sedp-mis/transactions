<?php

namespace SedpMis\Transactions\Repositories\DocumentTypeSignatory;

use SedpMis\Transactions\Models\Signatory;

class DocumentTypeSignatoryRepositoryEloquent implements DocumentTypeSignatoryRepositoryInterface
{
    public function findSignatoriesByTransaction($transaction, $documentTypes, $hierarchy = 1)
    {
        return Signatory::where('signatory_set_id', $transaction->signatory_set_id)
            ->orderBy('hierarchy')
            ->where('hierarchy', '>=', $hierarchy)
            ->forDocumentTypesWithMenu($documentTypes->pluck('id'), $transaction->menu_id)
            ->select('signatories.*', 'transaction_document_signatories.document_type_id')
            ->get();
    }
}
