<?php

namespace SedpMis\Transactions\Models\Interfaces;

interface DocumentTypeInterface
{
    /**
     * Find documentType(s) by code.
     *
     * @param  string|array $code
     * @return static|\Illuminate\Database\Eloquent\Collection
     */
    public function findByCode($code);
}
