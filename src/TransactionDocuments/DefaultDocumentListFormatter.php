<?php

namespace SedpMis\Transactions\TransactionDocuments;

use SedpMis\Transactions\Interfaces\DocumentListFormatterInterface;

class DefaultDocumentListFormatter implements DocumentListFormatterInterface
{
    /**
     * Format or transform document list.
     *
     * @param  \SedpMis\Transactions\Interfaces\DocumentListInterface $documentList
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    public function format($documentList)
    {
        return [
            'id'             => $documentList->documentType->id,
            'document_type'  => $documentList->documentType->name,
            'document_code'  => $documentList->documentType->code,
            'transaction_id' => $documentList->documents->first()->transaction_id,
            'print_type'     => $documentList->documentType->print_type,
            'type'           => 'document',
            'open_via'       => 'pdf',
            'sig_act_id'     => $documentList->signatoryAction ? $documentList->signatoryAction->id : null,
            'sig_act_name'   => $documentList->signatoryAction ? $documentList->signatoryAction->name : null,
            'link'           => $documentList->link
        ];
    }
}
