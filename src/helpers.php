<?php

if (!function_exists('transaction')) {
    /**
     * Return the transaction service.
     *
     * @return \SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface
     */
    function transaction()
    {
        return app(\SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface::class);
    }
}

if (!function_exists('document_generator')) {
    /**
     * Return the document_generator service.
     *
     * @return \SedpMis\Transactions\Repositories\Transaction\TransactionRepositoryInterface
     */
    function document_generator()
    {
        return app(\SedpMis\Transactions\Lib\DocumentGenerator::class);
    }
}
