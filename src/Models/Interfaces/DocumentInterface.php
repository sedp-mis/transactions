<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface DocumentInterface
{
    /**
     * Document signatories relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documentSignatories();
}
