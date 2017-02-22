<?php

namespace SedpMis\Transactions\Repositories\Transaction;

interface TransactionRepositoryInterface
{
    /**
     * Queue a transaction for approval.
     *
     * @param  array|\SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  int|\SedpMis\Transactions\Models\Interfaces\SignatorySetInterface $signatorySet
     * @throws \InvalidArgumentException
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    public function queue($transaction, $signatorySet = null);

    /**
     * Accept a transaction by the currentSignatory.
     *
     * @param  \Transaction $transaction
     * @param  \Signatory $signatory
     * @param  string $remarks
     * @return void
     */
    public function accept($transaction, $signatory, $remarks);

    /**
     * Reject a transaction by the currentSignatory.
     *
     * @param  \Transaction $transaction
     * @param  \Signatory $signatory
     * @param  string $remarks
     * @return void
     */
    public function reject($transaction, $signatory, $remarks);

    /**
     * Hold a transaction by the currentSignatory.
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
