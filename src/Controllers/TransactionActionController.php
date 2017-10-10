<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\SignatoryInterface;
use SedpMis\Transactions\Models\Interfaces\UserInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

class TransactionActionController extends \Illuminate\Routing\Controller
{
    /**
     * Request instance.
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Transaction repository.
     *
     * @var \SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface
     */
    protected $transaction;

    /**
     * User model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\UserInterface
     */
    protected $user;

    /**
     * Signatory model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\SignatoryInterface
     */
    protected $signatory;

    /**
     * Construct.
     *
     * @param Request                        $request
     * @param UserInterface                  $user
     * @param TransactionRepositoryInterface $transaction
     */
    public function __construct(
        Request $request,
        UserInterface $user,
        SignatoryInterface $signatory,
        TransactionRepositoryInterface $transaction
    ) {
        $this->user = $user;

        $this->request = $request;

        $this->signatory = $signatory;

        $this->transaction = $transaction;
    }

    /**
     * Put an action to the transaction.
     *
     * @param  int $transactionId
     * @param  string $action
     * @return Response
     */
    public function action($transactionId, $action)
    {
        $remarks = $this->request->input('remarks', '');

        $transaction = $this->transaction->with([
            'transactionApprovals.signatoryAction',
            ])->findOrFail($transactionId);

        $user = $this->user->find(get_user_session());

        if (!$this->isUserAllowedForAction($transaction, $user)) {
            throw App::make('sedpmis-transaction.validation_exception', 'Cannot set action on transaction if user is not the current or previous user signatory.');
        }

        $approval = $transaction->getCurrentApproval();

        // Set approval to the previous user approval (Note: approval means the user approver)
        if ($transaction->getPreviousApproval() && $transaction->getPreviousApproval()->user_id == $user->id) {
            $approval = $transaction->getPreviousApproval();
        }

        $action = $this->parseAction($action);

        if ($action == 'a') {
            $this->transaction->accept($transaction, $approval, $remarks);
        } elseif ($action == 'r') {
            $this->transaction->reject($transaction, $approval, $remarks);
        } elseif ($action == 'h') {
            $this->transaction->hold($transaction, $approval, $remarks);
        }
    }

    /**
     * Identify if user is allowed for action for a transaction.
     *
     * @param \SedpMis\Transactions\Models\Interfaces\TransactionInterface $transaction
     * @param \SedpMis\Transactions\Models\Interfaces\UserInterface $user
     * @return bool
     */
    protected function isUserAllowedForAction($transaction, $user)
    {
        if (
            $user->id == $transaction->getCurrentApproval()->user_id ||
            $user->id == $transaction->getPreviousApproval()->user_id
        ) {
            return true;
        }

        return false;
    }

    /**
     * Return the action first letter to interpret the desired action.
     *
     * @param  string $action
     * @return string
     */
    protected function parseAction($action)
    {
        return strtolower(head(str_split($action)));
    }

    /**
     * Translate an action code to word.
     *
     * @param  string $action
     * @return string
     */
    protected function actionToWord($action)
    {
        $words = [
            'a' => 'accept',
            'r' => 'reject',
            'h' => 'hold',
        ];

        return $words[$this->parseAction($action)];
    }
}
