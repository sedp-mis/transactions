<?php

namespace Repositories\DocumentSignatory;

interface DocumentSignatoryRepositoryInterface
{
    /**
     * Set branch id to fetch branch user signatories.
     *
     * @param int $branchId
     */
    public function setBranchId($branchId);

    /**
     * Get signatories of a document.
     *
     * @param $documentId|\Document
     * @return collection
     */
    public function getSignatories($documentId);
}
