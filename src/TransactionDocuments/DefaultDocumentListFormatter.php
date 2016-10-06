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
        return $documentList;
    }
}
