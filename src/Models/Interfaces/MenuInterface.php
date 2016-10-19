<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface MenuInterface
{
    /**
     * Transaction event handlers relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function transactionEventHandlers();
}
