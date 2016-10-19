<?php

namespace SedpMis\Transactions;

/**
 * Abstract class to provide methods on your concrete event handlers.
 */
abstract class BaseEventHandler
{
    public function approving($transaction)
    {
        // Nothing here...
    }

    public function approved($transaction)
    {
        // Nothing here...
    }

    public function rejecting($transaction)
    {
        // Nothing here...
    }

    public function rejected($transaction)
    {
        // Nothing here...
    }
}
