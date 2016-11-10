<?php

namespace SedpMis\Transactions\Repositories\Signatory;

use Illuminate\Support\Facades\DB;
use SedpMis\BaseRepository\RepositoryInterface;
use SedpMis\BaseRepository\BaseRepositoryEloquent;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use SedpMis\Transactions\Models\Interfaces\SignatoryInterface;
use SedpMis\Transactions\Models\Interfaces\SignatorySetInterface;

class SignatoryRepositoryEloquent extends BaseRepositoryEloquent implements SignatoryRepositoryInterface, RepositoryInterface
{
    /**
     * Signatory set model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\SignatorySetInterface
     */
    protected $signatorySet;

    /**
     * Construct.
     *
     * @param \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $model
     * @param \SedpMis\Transactions\Models\Interfaces\SignatorySetInterface $signatorySet
     */
    public function __construct(SignatoryInterface $model, SignatorySetInterface $signatorySet)
    {
        $this->model        = $model;
        $this->signatorySet = $signatorySet;
    }

    /**
     * Get the first signatory of a signatory set.
     *
     * @param  int $signatorySetId Signatory set ID
     * @return \SedpMis\Transactions\Models\Interfaces\SignatoryInterface
     */
    public function firstSignatory($signatorySetId)
    {
        return $this->signatoryAtHierarchy($signatorySetId, 1);
    }

    /**
     * Get the next signatory of a given signatory id.
     *
     * @param  int $signatoryId Signatory ID
     * @return \SedpMis\Transactions\Models\Interfaces\SignatoryInterface
     */
    public function nextSignatory($signatoryId)
    {
        $signatory = $this->model->find($signatoryId);

        return $this->signatoryAtHierarchy($signatory->signatory_set_id, $signatory->hierarchy + 1);
    }

    /**
     * Get the previous signatory of a given signatory id.
     *
     * @param  int $signatoryId
     * @return \SedpMis\Transactions\Models\Interfaces\SignatoryInterface
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
     * @return \SedpMis\Transactions\Models\Interfaces\SignatoryInterface
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

    /**
     * Return the default reversal signatory set.
     *
     * @return \SedpMis\Transactions\Models\Interfaces\SignatorySetInterface
     */
    public function defaultReversalSignatorySet()
    {
        $signatorySet = $this->signatorySet->where('is_for_reversal', 1)->first();

        if (is_null($signatorySet)) {
            throw new ModelNotFoundException('Default reversal signatory set is not found.');
        }

        return $signatorySet;
    }

    /**
     * Find the documentType's signatories.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param  \Illuminate\Database\Eloquent\Collection $documentTypes
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function findDocumentTypeSignatories($transaction, $documentTypes)
    {
        $configs = $this->model->newCollection(DB::table('transaction_document_signatories')->where('menu_id', $transaction->menu_id)
            ->whereIn('document_type_id', $documentTypes->lists('id'))
            ->get());

        $signatories = $this->model->find($configs->lists('signatory_id'));

        foreach ($signatories as $signatory) {
            $documentTypeIds = $configs->filter(function ($config) use ($signatory) {
                return $signatory->id == $config->signatory_id;
            })->lists('document_type_id');

            $signatory->setRelation('documentTypes', $documentTypes->filter(function ($documentType) use ($documentTypeIds) {
                return in_array($documentType->id, $documentTypeIds);
            }));
        }

        return $signatories;
    }
}
