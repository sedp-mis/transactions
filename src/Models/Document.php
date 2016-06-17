<?php

namespace SedpMis\Transactions\Models;

class Document extends \Eloquent
{
    protected $fillable = ['transaction_id', 'prepared_by', 'document_type_id', 'branch_id', 'is_archived', 'file_path'];

    public function scopeWithSignatories($query, $documentId = null)
    {
        $branchId = get_branch_session();

        if ($documentId !== null) {
            $query->where('documents.id', '=', $documentId);
        }

        $query->addSelect([
            'signatory_actions.name as sign_action',
            'users.fname',
            'users.lname',
            'users.mname',
            'jobs.name as sign_job',
        ]);

        $query
            ->withTransaction()
            ->leftJoin('transaction_signatory_sets', 'transaction_signatory_sets.menu_id', '=', 'transactions.transaction_menu_id')
            ->leftJoin('signatories', 'signatories.signatory_set_id', '=', 'transaction_signatory_sets.signatory_set_id')
            // ->leftJoin('users', 'users.id', '=', 'user_id')
            ->leftJoin('users', function ($join) use ($branchId) {
                $join->on('users.id', '=', \DB::raw("
                        IFNULL(
                            `signatories`.`user_id`, 
                            (
                                SELECT id FROM users U
                                WHERE 
                                    U.job_id = signatories.job_id AND 
                                    U.branch_id = {$branchId}
                            )
                        ) 
                    "));
            })
            ->leftJoin('signatory_actions', 'signatory_actions.id', '=', 'signatories.signatory_action_id')
            ->leftJoin('jobs', 'jobs.id', '=', 'users.job_id');
    }

    public function scopeWithPreparedBy($query, $documentId = null)
    {
        if ($documentId !== null) {
            $query->where('documents.id', '=', $documentId);
        }

        $query->select([
            'jobs.name as sign_job',
            'users.fname',
            'users.mname',
            'users.lname',
            \DB::raw("'Prepared By' as `sign_action`"),
        ]);

        $query
            ->withTransaction()
            ->leftJoin('users', 'users.id', '=', 'transactions.transacted_by')
            ->leftJoin('jobs', 'jobs.id', '=', 'users.job_id');
    }

    public function scopeWithTransaction($query)
    {
        $query
            ->leftJoin('transactions', 'transactions.id', '=', 'documents.transaction_id');
    }

    public function scopeWithTransactionMenu($query)
    {
        $query->addSelect([
            'menus.name as transaction_menu_name',
        ]);

        $query
            ->withTransaction()
            ->leftJoin('menus', 'menus.id', '=', 'transactions.transaction_menu_id');
    }

    public function scopeTypeOf($query, array $documentTypeCodes)
    {
        $query->leftJoin('document_types', 'document_types.id', '=', 'documents.document_type_id')
              ->whereIn('document_types.code', $documentTypeCodes);
    }

    public function transaction()
    {
        return $this->belongsTo('Transaction');
    }

    public function journalHead()
    {
        return $this->hasOne('JournalHead');
    }

    public function scopeWithDocumentType($query)
    {
        $query->join('document_types', 'document_types.id', '=', 'documents.document_type_id');
    }

    public function documentType()
    {
        return $this->belongsTo('DocumentType');
    }

    public function filterActualSignatories($signatories)
    {
        $signatoryIds = DB::table('transaction_document_signatories')->where('transaction_menu_id', $this->transaction->transaction_menu_id)
            ->where('document_type_id', $this->document_type_id)
            ->lists('signatory_id');

        return $signatories->filter(function ($signatory) use ($signatoryIds) {
            return in_array($signatory->id, $signatoryIds);
        });
    }
}
