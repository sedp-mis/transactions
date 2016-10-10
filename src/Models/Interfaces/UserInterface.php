<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface UserInterface
{
    /**
     * Job relation.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function job();
}
