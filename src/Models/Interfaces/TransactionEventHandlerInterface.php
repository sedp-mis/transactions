<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface TransactionEventHandlerInterface
{
    /**
     * Menus relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function menus();
}
