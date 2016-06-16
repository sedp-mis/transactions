<?php

namespace SedpMis\Transactions\Repositories\Signatory;

use Abstractions\Repository\BaseRepositoryEloquent;
use Abstractions\Repository\RepositoryInterface;
use Signatory;

class SignatoryRepositoryEloquent extends BaseRepositoryEloquent implements SignatoryRepositoryInterface, RepositoryInterface
{
    /**
     * `__construct` constructing class or model to be use.    
     * @param Signatory $model [description]
     */
    public function __construct(Signatory $model)
    {
        $this->model = $model;
    }

    /**
     * Get the first signatory of a signatory set.
     * 
     * @param  int $signatorySetId Signatory set ID
     * @return \Signatory
     */
    public function firstSignatory($signatorySetId)
    {
        return $this->signatoryAtHierarchy($signatorySetId, 1);
    }

    /**
     * Get the next signatory of a given signatory id.
     * 
     * @param  int $signatoryId Signatory ID
     * @return \Signatory
     */
    public function nextSignatory($signatoryId)
    {
        $signatory = $this->model->find($signatoryId);

        return $this->signatoryAtHierarchy($signatory->signatory_set_id, $signatory->hierarchy + 1);
    }

    /**
     * Get the previous signatory of a given signatory id.
     * 
     * @param  int $signatoryId Signatory ID
     * @return \Signatory
     */
    public function previousSignatory($signatoryId)
    {
        $signatory = $this->model->find($signatoryId);

        return $this->signatoryAtHierarchy($signatory->signatory_set_id, $signatory->hierarchy - 1);
    }

    /**
     * Get the signatory of a given signatory set id and the hierarchy position.
     * 
     * @param  int $signatorySetId Signatory set ID
     * @param  int $hierarchy      The hierarchy position of the signatory
     * @return \Signatory
     */
    public function signatoryAtHierarchy($signatorySetId, $hierarchy)
    {
        return $this->model->where('signatory_set_id', $signatorySetId)
            ->where('hierarchy', $hierarchy)
            ->first();
    }

    /**
     * Get the signatories from the given starting hierarchy.
     * 
     * @param  int $signatorySetId Signatory set ID
     * @param  int $hierarchy      Starting hierarchy to be fetched
     * @return collection
     */
    public function signatoryFromHierarchy($signatorySetId, $hierarchy)
    {
        $hierarchy = $hierarchy ?: 1;

        return $this->eagerLoadRelations()->fromHierarchy($signatorySetId, $hierarchy)
            ->get();
    }
}
