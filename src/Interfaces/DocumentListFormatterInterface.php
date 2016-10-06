<?php

namespace SedpMis\Transactions\Interfaces;

interface DocumentListFormatterInterface
{
    /**
     * Format or transform document list.
     *
     * @param  \SedpMis\Transactions\Interfaces\DocumentListInterface $documentList
     * @return array|\Illuminate\Database\Eloquent\Collection
     */
    public function format($documentList);
}
