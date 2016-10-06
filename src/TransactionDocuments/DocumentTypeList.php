<?php

namespace SedpMis\Transactions\TransactionDocuments;

use SedpMis\Transactions\Interfaces\DocumentListInterface;
use Illuminate\Support\Facades\DB;

/**
 * Responsible for listing documents per document type.
 * Formerly called documentService.
 */
class DocumentTypeList implements DocumentListInterface
{
    /**
     * URI link for the documents of the document type.
     *
     * @var string
     */
    public $link;

    /**
     * Document type of the document list.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $documentType;

    /**
     * Signatory action for the document type list.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    public $signatoryAction;

    /**
     * Documents under same document type list.
     *
     * @var \Illuminate\Database\Eloquent\Collection
     */
    public $documents;

    /**
     * List documents per document type.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function lists($transaction)
    {
        $documentLists = collection();

        foreach ($transaction->documents->groupBy('document_type_id') as $documents) {
            $documentList = new static;

            // Set relation models.
            $documentList->documentType = $documents->first()->documentType;
            $documentList->documents    = $documents;

            if ($this->isForSignatory($transaction->transaction_menu_id, $documentList->documentType->id, $transaction->curSignatory->id)) {
                $documentList->signatoryAction = $transaction->curSignatory->signatoryAction;
            }

            $documentList->link = DocumentLinkFactory::make($documentList->documentType)->link($transaction, $documents->first());

            $documentLists[] = $documentList;
        }

        return $documentLists;
    }

    /**
     * Identify whether if document type is for signatory under a certain menu.
     *
     * @param  int  $transactionMenuID
     * @param  int  $documentTypeId
     * @param  int  $signatoryId
     * @return bool
     */
    protected function isForSignatory($transactionMenuID, $documentTypeId, $signatoryId)
    {
        return DB::table('transaction_document_signatories')
            ->where('document_type_id', $documentTypeId)
            ->where('signatory_id', $signatoryId)
            ->count() > 0;
    }
}
