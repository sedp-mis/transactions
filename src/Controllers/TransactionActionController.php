<?php

namespace SedpMis\Transactions\Controllers;

use SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface;
use SedpMis\Transactions\Models\Interfaces\SignatoryInterface;
use SedpMis\Transactions\Models\Interfaces\UserInterface;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use RuntimeException;

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
        DB::beginTransaction();

        $remarks = $this->request->input('remarks', '');

        $transaction = $this->transaction->with([
            'curSignatory.signatoryAction',
            'curUserSignatory',
            'lastTransactionApproval.signatoryAction',
            ])->find($transactionId);

        $user = $this->user->find(get_user_session());

        if ($transaction->current_user_signatory != $user->id && $transaction->lastTransactionApproval->user_id != $user->id) {
            throw App::make('sedpmis-transaction.validation_exception', 'Cannot set action on transaction if user is not the current or previous user_signatory.');
        }

        // Assign the signatory that set an action to the transaction
        $signatory = $transaction->curSignatory;
        $signatory->setRelation('user', $transaction->curUserSignatory);

        // Set signatory to the previous user signatory
        if ($transaction->current_user_signatory != $user->id) {
            $signatory = $this->signatory->newInstance([
                'user_id'             => $user->id,
                'signatory_action_id' => $transaction->lastTransactionApproval->signatory_action_id,
            ]);
            $signatory->id = $transaction->lastTransactionApproval->signatory_id;
            $signatory->setRelation('user', $user);
            $signatory->setRelation('signatoryAction', $transaction->lastTransactionApproval->signatoryAction);
        }

        if (is_null($signatory->id)) {
            throw new RuntimeException("Trying to {$this->actionToWord($action)} by a signatory which id is null, on transaction {$transactionId}.");
        }

        $action = $this->parseAction($action);

        if ($action == 'a') {
            $this->transaction->accept($transaction, $signatory, $remarks);
        } elseif ($action == 'r') {
            $this->transaction->reject($transaction, $signatory, $remarks);
        } elseif ($action == 'h') {
            $this->transaction->hold($transaction, $signatory, $remarks);
        }

        DB::commit();
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
