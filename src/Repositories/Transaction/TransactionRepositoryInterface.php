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
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $remarks
     * @return void
     */
    public function accept($transaction, $approval = null, $remarks = '');

    /**
     * Reject a transaction by the currentApproval.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $remarks
     * @return void
     */
    public function reject($transaction, $approval = null, $remarks = '');

    /**
     * Hold a transaction by the current approval.
     * Be aware that a transaction can be hold by the system (not by signatory) in case there is a
     * transaction reversal for that transaction. In that case only status is set to H and has no record
     * in transaction approvals. The reference for that kind of transaction
     * is via the ref_transaction_id (reversalTransactions relation, referenceTransaction is the inverse of relation).
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @param  string $remarks
     * @return void
     */
    public function hold($transaction, $approval = null, $remarks = '');

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

    /**
     * Get approved transactions for the user.
     *
     * @param  int $userId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApprovedTransactions($userId);
}
