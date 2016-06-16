<?php

namespace SedpMis\Transactions\Repositories\Signatory;

interface SignatoryRepositoryInterface
{
    /**
     * Get the first signatory of a signatory set.
     * 
     * @param  int $signatorySetId Signatory set ID
     * @return \Signatory
     */
    public function firstSignatory($signatorySetId);

    /**
     * Get the next signatory of a given signatory id.
     * 
     * @param  int $signatoryId Signatory ID
     * @return \Signatory
     */
    public function nextSignatory($signatoryId);

    /**
     * Get the previous signatory of a given signatory id.
     * 
     * @param  int $signatoryId Signatory ID
     * @return \Signatory
     */
    public function previousSignatory($signatoryId);

    /**
     * Get the signatory of a given signatory set id and the hierarchy position.
     * 
     * @param  int $signatorySetId Signatory set ID
     * @param  int $hierarchy      The hierarchy position of the signatory
     * @return \Signatory
     */
    public function signatoryAtHierarchy($signatorySetId, $hierarchy);

    /**
     * Get the signatories from the given starting hierarchy.
     * 
     * @param  int $signatorySetId Signatory set ID
     * @param  int $hierarchy      Starting hierarchy to be fetched
     * @return collection
     */
    public function signatoryFromHierarchy($signatorySetId, $hierarchy);
}
