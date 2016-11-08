<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface DocumentApprovalInterface
{
    /**
     * SignatoryAction relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function signatoryAction();

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
