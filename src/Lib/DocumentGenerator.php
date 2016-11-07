<?php

namespace SedpMis\Transactions\Lib;

use SedpMis\Transactions\Models\Interfaces\DocumentTypeInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentInterface;

class DocumentGenerator
{
    /**
     * Array of documentType codes to be generated.
     *
     * @var array
     */
    protected $documentTypeCodes = [];

    /**
     * DocumentType model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\DocumentTypeInterface
     */
    protected $documentType;

    /**
     * Document model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\DocumentInterface
     */
    protected $document;

    /**
     * Constructor.
     *
     * @param DocumentTypeInterface $documentType
     * @param DocumentInterface     $document
     */
    public function __construct(DocumentTypeInterface $documentType, DocumentInterface $document)
    {
        $this->documentType = $documentType;
        $this->document     = $document;
    }

    /**
     * Generate the documents for a transaction.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  array  $documentTypeCodes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generate($transaction, $documentTypeCodes = [])
    {
        $documentTypeCodes = $documentTypeCodes ?: $this->documentTypeCodes;

        $documentTypes = $this->documentType->findByCode($documentTypeCodes);

        $documents = $this->document->newCollection();

        foreach ($documentTypes as $documentType) {
            $documents[] = $this->document->create([
                'transaction_id'   => $transaction->id,
                'document_type_id' => $documentType->id,
            ]);
        }

        return $documents;
    }
}
