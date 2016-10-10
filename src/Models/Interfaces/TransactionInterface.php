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
     * Transaction approvals relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function transactionApprovals();

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
     * Reference transaction for reversal transaction.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function referenceTransaction();
}
