<?php

namespace SedpMis\Transactions\TransactionDocuments;

use Illuminate\Support\Facades\App;
use RuntimeException;

class DocumentLinkFactory
{
    protected $documentLinks = [];

    /**
     * Return the document link instance for the document type.
     *
     * @param  \Illuminate\Database\Eloquent\Model $documentType
     * @throws \RuntimeException When document_link is not set
     * @return \SedpMis\Transactions\Interfaces\DocumentLinkInteface
     */
    public function make($documentType)
    {
        // Make sure that there will be single instance for each documentLink classes.
        if (!is_null($documentType->document_link)) {
            if (!array_key_exists($documentType->document_link, $this->documentLinks)) {
                $this->documentLinks[$documentType->document_link] = App::make($documentType->document_link);
            }

            return $this->documentLinks[$documentType->document_link];
        }

        throw new RuntimeException("The document_link is not set in documentType {$documentType->code} (id={$documentType->id}).");
    }
}
