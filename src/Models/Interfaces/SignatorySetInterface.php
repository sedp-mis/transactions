<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface SignatorySetInterface
{
    /**
     * Signatories relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function signatories();
}
