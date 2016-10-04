<?php

namespace SedpMis\Transactions\Interfaces;

/**
 * Resolve the user from a specific signatory.
 */
interface UserResolverInterface
{
    /**
     * Return the user of a specific signatory.
     *
     * @param  \SedpMis\Transactions\Models\Interfaces\SignatoryInterface $signatory
     * @return \User
     */
    public function getUser($signatory);
}
