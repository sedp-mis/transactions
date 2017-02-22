<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface TransactionInterface
{
    /**
     * Last transaction approval relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function lastTransactionApproval();

    /**
     * Documents relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documents();

    /**
     * Menu relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function menu();
    
    /**
     * Current user signatory relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentUser();

    /**
     * Current signatory relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function currentSignatory();

    /**
     * Transaction approvals relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionApprovals();

    /**
     * Reference transaction for reversal transaction.
     * Inverse of reversalTransactions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referenceTransaction();

    /**
     * Reversal transactions relation.
     * Inverse of referenceTransaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reversalTransactions();

    /**
     * Reversal transaction that causes a transaction to be reversed.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function reversedByTransaction();

    /**
     * Get the current approval.
     *
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface
     */
    public function getCurrentApproval();

    /**
     * Get the next approval.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface
     */
    public function getNextApproval($approval = null);

    /**
     * Get the previous approval.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface $approval
     * @return \SedpMis\Transactions\Models\Interfaces\TransactionApprovalInterface
     */
    public function getPreviousApproval($approval = null);
}
