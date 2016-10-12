<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface;
use Illuminate\Http\Request;

/**
 * Controller for returning transaction list.
 * Pending transactions are queried where current_user_signatory equals the user_id and status equals 'Q'.
 * Inherited methods: index, show.
 */
class TransactionsController extends \SedpMis\BaseApi\BaseApiController
{
    /**
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Construct.
     *
     * @param TransactionRepositoryInterface $repo
     */
    public function __construct(TransactionRepositoryInterface $repo, Request $request)
    {
        $this->repo    = $repo;
        $this->request = $request;

        $this->repo->applyQueryParams($this->request);
    }

    /**
     * Return the pending transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function pending()
    {
        return $this->repo->filters([
            'current_user_id' => get_user_session(),
            'status'          => 'Q',
        ])
        ->get();
    }

    /**
     * Return the approved transactions.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function approved()
    {
        return $this->repo->filters([
            'status'          => 'A',
        ])
        ->get();
    }

    /**
     * Return transactions which is currently in queue, for live-tracking.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function tracker()
    {
        return $this->repo->getTrackedTransactions(get_user_session());
    }

    /**
     * Return historical transaction.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function history()
    {
        return $this->repo->getHistoricalTransactions(get_user_session());
    }
}
