<?php

namespace SedpMis\Transactions\Repositories\DocumentTypeSignatory;

interface DocumentTypeSignatoryRepositoryInterface
{
    public function findSignatoriesByTransaction($transaction, $documentTypes);
    public function findSignatoriesByMenu($menuId, $documentTypeIds = []);
}
