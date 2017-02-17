<?php

namespace SedpMis\Transactions\Repositories\DocumentTypeSignatory;

use DB;
use App;

class DocumentTypeSignatoryRepositoryEloquent implements DocumentTypeSignatoryRepositoryInterface
{
    protected $signatory;

    public function __construct()
    {
        $this->signatory = App::make('SedpMis\Transactions\Models\Interfaces\SignatoryInterface');
    }

    // Deprecated.
    // It is moved to signatory repository.
    public function findSignatoriesByMenu($menuId, $documentTypeIds = [])
    {
        $q = DB::table('transaction_document_signatories')->where('menu_id', $menuId);

        if ($documentTypeIds) {
            $q->whereIn('document_type_id', $documentTypeIds);
        }

        return $this->signatory->with([
            'signatoryAction',
            'user',
            'job',
        ])->find($q->lists('signatory_id'));
    }
}
