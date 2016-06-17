<?php

namespace Repositories\DocumentSignatory;

use DocumentSignatory;
use DocumentApproval;
use Document;
use Signatory;

class DocumentSignatoryRepositoryEloquent implements DocumentSignatoryRepositoryInterface
{
    /**
     * Branch id.
     *
     * @var int
     */
    protected $branchId;

    /**
     * Set branch id to fetch branch user signatories.
     *
     * @param int $branchId
     */
    public function setBranchId($branchId)
    {
        $this->branchId = (int) $branchId;

        return $this;
    }

    /**
     * Get array formatted document signatories.
     *
     * @param  int $documentId
     * @return array
     */
    public function getSignatories($documentId)
    {
        $signatories = $this->findSignatories($documentId);
        $rows        = [];

        $h = 0;
        foreach ($signatories as $signatory) {
            $rows[] = [
                'fname'               => $signatory->user->fname,
                'mname'               => $signatory->user->mname,
                'lname'               => $signatory->user->lname,
                'sign_job'            => $signatory->user->job->name,
                'full_name'           => $signatory->user->full_name,
                'sign_action'         => $signatory->signatoryAction->name,
                'user_id'             => $signatory->user->id,
                'signatory_id'        => $signatory->id,
                'signatory_action_id' => $signatory->signatoryAction->id,
                'hierarchy'           => ++$h,
                'is_signed'           => $signatory instanceof DocumentApproval ? 1 : 0,
            ];
        }

        return $rows;
    }

    /**
     * Find document signatories models of a document.
     *
     * @param $documentId|\Document
     * @return collection
     */
    public function findSignatories($documentId)
    {
        $document = $documentId instanceof Document ? $documentId : Document::with('transaction.curSignatory')->find($documentId);

        // Get the actual signatories by using documentApproval.
        $documentApprovals = DocumentApproval::with(['user', 'signatoryAction'])->where('document_id', $documentId)->orderBy('created_at')->get();

        $signatories = Signatory::with('signatoryAction')
            ->where('signatory_set_id', $document->transaction->curSignatory->signatory_set_id)
            ->whereRaw('id in (select signatory_id from transaction_document_signatories where '.
                "transaction_menu_id = {$document->transaction->transaction_menu_id} and document_type_id = {$document->document_type_id})")
            ->skip($documentApprovals->count())
            ->limit(100)
            ->get();

        $documentSignatories = $this->toDocumentSignatories(
            $signatories
        );

        return $documentApprovals->addMany($documentSignatories);
    }

    protected function toDocumentSignatories($signatories)
    {
        $docSignatories = collection();
        foreach ($signatories as $signatory) {
            $docSignatory                   = new DocumentSignatory;
            $docSignatory->id               = $signatory->id;
            $docSignatory->signatory_set_id = $signatory->signatory_set_id;

            $docSignatory->setRelation('signatoryAction', $signatory->signatoryAction);
            $docSignatory->setRelation('user', $signatory->getUser($this->branchId));

            $docSignatories[] = $docSignatory;
        }

        return $docSignatories;
    }
}
