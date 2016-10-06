<?php

namespace SedpMis\Transactions\Interfaces;

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
