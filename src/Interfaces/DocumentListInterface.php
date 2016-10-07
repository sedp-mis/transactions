<?php

namespace SedpMis\Transactions\Interfaces;

/**
 * Responsible for listing documents.
 */
interface DocumentListInterface
{
    /**
     * List documents.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function lists($transaction);
}
