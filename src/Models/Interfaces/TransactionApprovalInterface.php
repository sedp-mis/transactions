<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface TransactionApprovalInterface
{
    /**
     * SignatoryAction relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function signatoryAction();

    /**
     * Signatory relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function signatory();

    /**
     * User relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user();

    /**
     * Job relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function job();
}
