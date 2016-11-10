<?php

namespace SedpMis\Transactions\Reports\BaseDocument;

use SedpMis\Transactions\Repositories\DocumentTypeSignatory\DocumentTypeSignatoryRepositoryInterface;
use SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface;
use SedpMis\Transactions\Helpers\DocumentSignatoryHelper;

abstract class BaseDocumentController extends \Illuminate\Routing\Controller
{
    /**
     * Repository for documentType's signatories.
     *
     * @var \SedpMis\Transactions\Repositories\DocumentTypeSignatory\DocumentTypeSignatoryRepositoryInterface
     */
    protected $documentTypeSignatory;

    /**
     * Transaction repository.
     *
     * @var \SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface
     */
    protected $transaction;

    /**
     * Document type Ids for the report.
     *
     * @var array
     */
    protected $documentTypeIds = [];

    /**
     * Eagerload relations with transaction.
     *
     * @var array
     */
    protected $eagerLoadWithTransaction = [];

    public function __construct(
        DocumentTypeSignatoryRepositoryInterface $documentTypeSignatory,
        TransactionRepositoryInterface $transaction
    ) {
        $this->documentTypeSignatory = $documentTypeSignatory;
        $this->transaction           = $transaction;

        $this->boot();
    }

    /**
     * Boot anything in the controller.
     *
     * @return void
     */
    protected function boot()
    {
        // Nothing here.
    }

    /**
     * Return the document type ids.
     *
     * @return array
     */
    public function getDocumentTypeIds()
    {
        return $this->documentTypeIds;
    }

    /**
     * Show the document(s).
     *
     * @param  int $transactionId
     * @return void
     */
    public function show($transactionId)
    {
        $transaction = $this->transaction->withDocumentTypes($this->getDocumentTypeIds())
            ->with(array_merge([
                'branch',
                'menu',
                'documents.documentSignatories.signatoryAction',
                'documents.documentSignatories.user',
                'documents.documentSignatories.job',
                'documents.documentType',
                'transactionApprovals',
            ], $this->eagerLoadWithTransaction))
            ->findOrFail($transactionId);

        // If transaction is still in queue, signatories from settings will retrieved and set them as signatories for the documents
        if ($transaction->status == 'Q') {
            $signatories = $this->documentTypeSignatory->findSignatoriesByTransaction(
                $transaction,
                collection($transaction->documents->pluck('documentType')),
                $transaction->transactionApprovals->count() + 1
            );

            $transaction->documents = DocumentSignatoryHelper::setDocumentSignatories($transaction->documents, $signatories);
        }

        return $this->loadReportPdf($transaction);
    }

    /**
     * Load pdf report.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @return void
     */
    abstract public function loadReportPdf($transaction);
}
