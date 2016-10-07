<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Interfaces\DocumentListFormatterInterface;
use SedpMis\Transactions\TransactionDocuments\DocumentListFactory;
use SedpMis\Transactions\Models\Interfaces\TransactionInterface;

class TransactionDocumentsController extends \Illuminate\Routing\Controller
{
    /**
     * Transaction model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    protected $transaction;

    /**
     * Document list formatter.
     *
     * @var \SedpMis\Transactions\Interfaces\DocumentListFormatterInterface
     */
    protected $documentListFormatter;

    /**
     * Construct.
     *
     * @param TransactionInterface           $transaction
     * @param DocumentListInterface          $documentList
     * @param DocumentListFormatterInterface $documentListFormatter
     */
    public function __construct(
        TransactionInterface $transaction,
        DocumentListFormatterInterface $documentListFormatter
    ) {
        $this->transaction           = $transaction;
        $this->documentListFormatter = $documentListFormatter;
    }

    /**
     * Return transaction document list.
     *
     * @param  int $transactionId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function documentList($transactionId)
    {
        $transaction = $this->transaction->with([
            'documents.documentType', 'curUserSignatory', 'curSignatory.signatoryAction', 'menu.documentService',
        ])->findOrFail($id);

        return (new DocumentListFactory)->make($transaction->menu)->lists($transaction)->transform(function ($documentList) {
            return $this->documentListFormatter->format($documentList);
        });

    }
}
