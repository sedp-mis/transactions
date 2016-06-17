<?php

namespace SedpMis\Transactions\Repositories\MenuSignatory;

interface DocumentTypeSignatoryRepositoryInterface
{
    public function findSignatoriesByTransaction($transaction, $documentTypes);
}