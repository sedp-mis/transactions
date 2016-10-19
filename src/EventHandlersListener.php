<?php

namespace SedpMis\Transactions;

use Illuminate\Support\Facades\Event;
use SedpMis\Transactions\Models\Interfaces\TransactionEventHandlerInterface;

class EventHandlersListener
{
    /**
     * Identifier if this is already listening to events.
     *
     * @var bool
     */
    public static $listening = false;

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
     * Construct.
     *
     * @param TransactionEventHandlerInterface $transactionEventHandler
     */
    public function __construct(TransactionEventHandlerInterface $transactionEventHandler)
    {
        $this->transactionEventHandler = $transactionEventHandler;
    }

    /**
     * Listen menus' transactionEventHandlers to all transaction events.
     *
     * @return void
     */
    public function listen()
    {
        // Check if already listening.
        if (static::$listening) {
            return;
        }

        $eventHandlers = $this->transactionEventHandler->with(['menus' => function ($query) {
            $query->select('menus.id', 'transaction_event_handler_id', 'name');
        }])->has('menus')->get();

        foreach ($eventHandlers as $eventHandler) {
            foreach ($eventHandler->menus as $menu) {
                foreach ($this->events as $event) {
                    Event::listen("transaction_approval.{$menu->id}.{$event}", "{$eventHandler->class_path}@{$event}", $eventHandler->priority);
                }
            }
        }

        static::$listening = true;
    }
}
