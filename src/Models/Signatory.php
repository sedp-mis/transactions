<?php

namespace SedpMis\Transactions\Models;

use Illuminate\Support\Facades\DB;

class Signatory extends \Eloquent
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function userByJob($branchId)
    {
        if (!$this->job_id) {
            throw new \Exception('Cannort call method `userByJob()` when `job_id` attribute is not set.');
        }

        return User::findByBranchJob($branchId, $this->job_id);
    }

    public function signatoryAction()
    {
        return $this->belongsTo(SignatoryAction::class);
    }

    public function scopeForDocumentTypesWithMenu($query, array $documentTypeIds, $menuId)
    {
        return $query->join('transaction_document_signatories', function ($join) use ($documentTypeIds, $menuId) {
            $join
                ->on('transaction_document_signatories.signatory_id', '=', 'signatories.id')
                ->on('transaction_document_signatories.menu_id', '=', DB::raw($menuId))
                ->on('transaction_document_signatories.document_type_id', 'in', DB::raw('('.join(',', $documentTypeIds).')'));
        });
    }
}
