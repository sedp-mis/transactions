<?php

namespace SedpMis\Transactions\Lib;

use SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentSignatoryInterface;
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
     * DocumentSignatory model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\DocumentSignatoryInterface
     */
    protected $documentSignatory;

    /**
     * Signatory repository.
     *
     * @var \SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface
     */
    protected $signatory;

    /**
     * Constructor.
     *
     * @param \SedpMis\Transactions\Models\Interfaces\DocumentInterface                 $document
     * @param \SedpMis\Transactions\Models\Interfaces\DocumentTypeInterface             $documentType
     * @param \SedpMis\Transactions\Models\Interfaces\DocumentSignatoryInterface        $documentSignatory
     * @param \SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface $signatory
     */
    public function __construct(
        DocumentInterface $document,
        DocumentTypeInterface $documentType,
        DocumentSignatoryInterface $documentSignatory,
        SignatoryRepositoryInterface $signatory
    ) {
        $this->document          = $document;
        $this->signatory         = $signatory;
        $this->documentType      = $documentType;
        $this->documentSignatory = $documentSignatory;
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

        $this->generateDocumentSignatories($transaction, $documents);

        return $documents;
    }

    /**
     * Generate the documents signatories.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \Illuminate\Database\Eloquent\Collection $documents
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generateDocumentSignatories($transaction, $documents)
    {
        $signatories = $this->signatory->findDocumentTypeSignatories($transaction, collection($documents->pluck('documentType')));

        $documentLists = $documents->groupBy('document_type_id');

        $documentSignatories = collection();

        foreach ($documentLists as $documentTypeId => $documents) {
            // Find the signatories for the documentType
            $signatories = $signatories->filter(function ($signatory) use ($documentTypeId) {
                return in_array($documentTypeId, $signatory->documentTypes->lists('id'));
            });

            foreach ($documents as $document) {
                foreach ($signatories as $signatory) {
                    $documentSignatories[] = $this->documentSignatory->create([
                        'document_id'         => $document->id,
                        'signatory_id'        => $signatory->id,
                        'user_id'             => $signatory->getUser()->id,
                        'job_id'              => $signatory->getUser()->job_id,
                        'signatory_action_id' => $signatory->signatoryAction->id,
                    ]);
                }
            }
        }

        return $documentSignatories;
    }
}
