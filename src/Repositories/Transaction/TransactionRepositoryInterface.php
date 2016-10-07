<?php

namespace SedpMis\Transactions\Repositories\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * Queue a transaction for approval.
     *
     * @param  array|\Transaction $transaction
     * @return \Transaction
     */
    public function queue($transaction);

    /**
     * Save the accounting document of a transaction.
     *
     * @param  int $transactionId  [description]
     * @param  array|\Modules\Transaction\Data\AccountingDocument $acctDoc
     * @param  int $documentTypeId
     * @return \Document
     */
    public function saveAccountingDocument($transactionId, $acctDoc, $documentTypeId = null);

    /**
     * Accept a transaction by the curSignatory.
     *
     * @param  \Transaction $transaction
     * @param  \Signatory $signatory
     * @param  string $remarks
     * @return void
     */
    public function accept($transaction, $signatory, $remarks);

    /**
     * Reject a transaction by the curSignatory.
     *
     * @param  \Transaction $transaction
     * @param  \Signatory $signatory
     * @param  string $remarks
     * @return void
     */
    public function reject($transaction, $signatory, $remarks);

    /**
     * Hold a transaction by the curSignatory.
     *
     * @param  \Transaction $transaction
     * @param  \Signatory $signatory
     * @param  string $remarks
     * @return void
     */
    public function hold($transaction, $signatory, $remarks);

    /**
     * Get the tracked transactions for the involved user.
     *
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getTrackedTransactions($userId);

    /**
     * Get user's historical transactions.
     *
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistoricalTransactions($userId);
}
