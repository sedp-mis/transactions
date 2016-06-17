<?php

namespace SedpMis\Transactions\Helpers;

use Illuminate\Support\Facades\Session;

class DocumentSignatoryHelper
{
    public static function setDocumentSignatories($documents, $signatories)
    {
        foreach (static::setSignatoryUser($signatories) as $signatory) {
            foreach ($documents as $document) {
                if ($document->documentType->id == $signatory->document_type_id) {
                    $document->setRelation('signatories', $signatory);
                }
            }
        }

        return $documents;
    }

    public static function setSignatoryUser($signatories)
    {
        foreach ($signatories as $signatory) {
            if (!$signatory->user) {
                $signatory->setRelation('user', $signatory->userByJob(Session::get('branch_id')));
            }
        }

        return $signatories;
    }
}
