<?php

namespace SedpMis\Transactions\Repositories\JournalHead;

use SedpMis\BaseRepository\BaseBranchRepositoryEloquent;
use SedpMis\BaseRepository\BaseBranchRepositoryInterface;
use SedpMis\Transactions\Models\JournalHead;

class JournalHeadRepositoryEloquent extends BaseBranchRepositoryEloquent implements BaseBranchRepositoryInterface
{
    public function __construct(JournalHead $model)
    {
        $this->model = $model;
    }

    public function findByDocumentIds(array $documentIds)
    {
        return $this->eagerLoadRelations()->whereIn('document_id', $documentIds)->get();
    }
}