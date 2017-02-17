<?php

namespace SedpMis\Transactions\Repositories\DocumentTypeSignatory;

interface DocumentTypeSignatoryRepositoryInterface
{
    public function findSignatoriesByMenu($menuId, $documentTypeIds = []);
}
