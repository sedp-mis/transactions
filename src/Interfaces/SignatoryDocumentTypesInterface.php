<?php

namespace SedpMis\Transactions\Interfaces;

interface SignatoryDocumentTypesInterface
{
    /**
     * Find the document types of a menu per signatory.
     *
     * @param  int $menuId
     * @param  int $signatoryId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findDocumentTypes($menuId, $signatoryId);
}
