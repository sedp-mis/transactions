<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Interfaces\DocumentListFormatterInterface;
use SedpMis\Transactions\Models\Interfaces\TransactionInterface;
use SedpMis\Transactions\Interfaces\DocumentListInterface;

class TransactionDocumentsController extends \Illuminate\Routing\Controller
{
    /**
     * Transaction model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\TransactionInterface
     */
    protected $transaction;

    /**
     * Document list.
     *
     * @var \SedpMis\Transactions\Interfaces\DocumentListInterface
     */
    protected $documentList;

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
        DocumentListInterface $documentList,
        DocumentListFormatterInterface $documentListFormatter
    ) {
        $this->transaction           = $transaction;
        $this->documentList          = $documentList;
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

        return $this->documentList->lists($transaction)->transform(function ($documentList) {
            return $this->documentListFormatter->format($documentList);
        });
    }
}
