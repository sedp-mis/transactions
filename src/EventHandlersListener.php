<?php

namespace SedpMis\Transactions;

use Illuminate\Support\Facades\Event;
use SedpMis\Transactions\Models\Interfaces\TransactionEventHandlerInterface;
use SedpMis\Transactions\Interfaces\GlobalTransactionEventHandlersInterface;

class EventHandlersListener
{
    /**
     * Events on transaction approval.
     *
     * @var array
     */
    protected $events = [
        'approving',
        'approved',
        'rejecting',
        'rejected',
    ];

    /**
     * TransactionEventHandler model.
     *
     * @var \SedpMis\Transactions\Models\Interfaces\TransactionEventHandlerInterface
     */
    protected $transactionEventHandler;

    /**
     * Global TransactionEventHandlers.
     *
     * @var \SedpMis\Transactions\Interfaces\GlobalTransactionEventHandlersInterface
     */
    protected $globalTransactionEventHandlers;

    /**
     * Construct.
     *
     * @param TransactionEventHandlerInterface $transactionEventHandler
     * @param GlobalTransactionEventHandlersInterface $globalTransactionEventHandlers
     */
    public function __construct(
        TransactionEventHandlerInterface $transactionEventHandler,
        GlobalTransactionEventHandlersInterface $globalTransactionEventHandlers
    ) {
        $this->transactionEventHandler        = $transactionEventHandler;
        $this->globalTransactionEventHandlers = $globalTransactionEventHandlers;
    }

    /**
     * Listen menus' transactionEventHandlers to all transaction events.
     *
     * @return void
     */
    public function listen()
    {
        $eventHandlers = $this->transactionEventHandler->with(['menus' => function ($query) {
            $query->select('menus.id', 'transaction_event_handler_id', 'name');
        }])->has('menus')->get();

        $eventHandlers = $eventHandlers->merge($this->globalTransactionEventHandlers->getHandlers());

        foreach ($eventHandlers as $eventHandler) {
            foreach ($eventHandler->menus as $menu) {
                foreach ($this->events as $event) {
                    if (method_exists($eventHandler->class_path, $event)) {
                        Event::listen("transaction_approval.{$menu->id}.{$event}", "{$eventHandler->class_path}@{$event}", $eventHandler->priority);
                    }
                }
            }
        }
    }
}
