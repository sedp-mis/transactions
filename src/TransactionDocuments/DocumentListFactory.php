<?php

namespace SedpMis\Transactions\TransactionDocuments;

use Illuminate\Support\Facades\App;

class DocumentListFactory
{
    /**
     * Instantiate the documentList util.
     *
     * @param  \Illuminate\Database\Eloquent\Model $menu
     * @return \SedpMis\Transactions\Interfaces\DocumentListInterface
     */
    public function make($menu)
    {
        if (is_null($menu->documentList)) {
            return new DocumentTypeList;
        }

        return App::make($menu->documentList->class_path);
    }
}
