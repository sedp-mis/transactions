<?php

namespace SedpMis\Transactions\Lib;

use SedpMis\Transactions\Repositories\Signatory\SignatoryRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentSignatoryInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentTypeInterface;
use SedpMis\Transactions\Models\Interfaces\DocumentInterface;
use RuntimeException;

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
     * Generate the documents for a transaction based on document type codes.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  array  $documentTypeCodes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generate($transaction, $documentTypeCodes = [])
    {
        $documentTypeCodes = $documentTypeCodes ?: $this->documentTypeCodes;

        $documentTypes = $this->documentType->findByCode($documentTypeCodes);

        $foundDocTypeCodes = $documentTypes->lists('code');

        $diff = array_diff($documentTypeCodes, $foundDocTypeCodes);

        if (!empty($diff)) {
            $diffStr = join(', ', $diff);
            throw new RuntimeException("Some document type code does not exists: {$diffStr}.");
        }

        return $this->generateByIds($transaction, $documentTypes->lists('id'));
    }

    /**
     * Generate the document base on its ids.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  array  $documentTypeIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function generateByIds($transaction, array $documentTypeIds)
    {
        $documents = $this->document->newCollection();

        foreach ($documentTypeIds as $documentTypeId) {
            $documents[] = $this->document->create([
                'transaction_id'   => $transaction->id,
                'document_type_id' => $documentTypeId,
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

            $documentSignatories = $documentSignatories->merge(
                $this->createDocumentSignatories($documents, $signatories)
            );
        }

        return $documentSignatories;
    }

    /**
     * Create document signatories.
     *
     * @param  collection $documents
     * @param  collection $signatories
     * @return collection
     */
    public function createDocumentSignatories($documents, $signatories)
    {
        if (!is_collection($documents) && !is_array($documents)) {
            $documents = [$documents];
        }

        $documentSignatories = collection();

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

        return $documentSignatories;
    }

    /**
     * Create document signatories by signatory set.
     *
     * @param  collection $documents
     * @param  collection $signatorySet
     * @return collection
     */
    public function createDocumentSignatoriesBySet($documents, $signatorySet)
    {
        $signatories = is_numeric($signatorySet) ? $this->signatory->findSignatoriesOfSignatorySet($signatorySet) : $signatorySet->signatories;

        return $this->createDocumentSignatories($documents, $signatories);
    }
}
