<?php

namespace SedpMis\Transactions\TransactionDocuments;

use SedpMis\Transactions\Interfaces\DocumentListInterface;

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

        foreach ($this->getDocuments($transaction)->groupBy('document_type_id') as $documents) {
            $documentList = new static;

            $documents = collection($documents);

            // Set relation models.
            $documentList->documentType = $documents->first()->documentType;
            $documentList->documents    = $documents;
            $doc                        = $documents->first();

            $doc->load(['documentSignatories' => function ($q) use ($transaction) {
                $q->where('signatory_id', $transaction->current_signatory_id);
            }]);

            if ($doc->documentSignatories->count()) {
                $documentList->signatoryAction = $doc->documentSignatories->first()->signatoryAction;
            }

            $documentList->link = (new DocumentLinkFactory)->make($documentList->documentType)->link(
                $transaction->is_reversal && $transaction->referenceTransaction ? $transaction->referenceTransaction : $transaction,
                $documents->first()
            );

            $documentLists[] = $documentList;
        }

        return $this->sortDocumentLists($documentLists);
    }

    /**
     * Sort document lists.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $documentLists
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function sortDocumentLists($documentLists)
    {
        return $documentLists->sortBy(function ($dl) {
            return $dl->signatoryAction ? $dl->signatoryAction->name : 'z';
        })->values();
    }

    /**
     * Get the documents in the transaction.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getDocuments($transaction)
    {
        if ($transaction->is_reversal && $transaction->referenceTransaction) {
            return $transaction->referenceTransaction->documents;
        }

        return $transaction->documents;
    }
}
