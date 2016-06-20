<?php

namespace SedpMis\Transactions\Reports\AccountingDocuments;

class DataFormatter
{
    protected $transaction;

    public function __construct($transaction)
    {
        $this->transaction = $transaction;
    }

    public function headDetails()
    {
        $headDetails = [];

        $transaction = $this->transaction;

        foreach ($transaction->documents as $document) {
            $headDetails[] = [
                'branch_name' => $transaction->branch->name,
                'particulars' => $document->journalHead->particulars,
                'reference_number' => $document->journalHead->id,
                'date' => $transaction->transacted_at,
            ];
        }

        return $headDetails;
    }

    public function reportData()
    {
        $rows = [];

        foreach ($this->transaction->documents as $ref => $document) {
            foreach ($document->journalHead->journalEntries as $entry) {
                $rows[] = [
                    'ref'       => $ref + 1,
                    'acc_code'  => $entry->account->code,
                    'acc_title' => $entry->account->title,
                    'debit'     => $entry->debit,
                    'credit'    => $entry->credit,
                    'is_contra' => $entry->is_contra,
                ];
            }
        }

        return $rows;
    }

    public function signsData()
    {
        return [];
    }
}
