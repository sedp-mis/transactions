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
        $rows = [];
        foreach ($this->transaction->documents as $ref => $document) {
            $approvals = [];
            foreach ($document->documentApprovals as $approval) {
                $approvals[] = [
                    'user_id'     => $approval->user->id,
                    'fname'       => $approval->user->fname,
                    'mname'       => $approval->user->mname,
                    'lname'       => $approval->user->lname,
                    'full_name'   => $approval->user->full_name,
                    'sign_job'    => $approval->job->name,
                    'sign_action' => $approval->signatoryAction->name,
                    'is_signed'   => 1,
                ];
            }
            $rows[] = $approvals;
        }

        return $rows;
    }
}
