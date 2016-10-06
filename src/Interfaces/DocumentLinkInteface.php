<?php

namespace SedpMis\Transactions\Interfaces;

interface DocumentLinkInteface
{
    /**
     * Return the link for the documents.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \Illuminate\Database\Eloquent\Model $document
     * @return string
     */
    public function link($transaction, $document);
}
