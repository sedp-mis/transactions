<?php

namespace SedpMis\Transactions;

use Illuminate\Support\Facades\Route;

class Router
{
    /**
     * Register scaffolding transaction routes.
     *
     * @return void
     */
    public static function route()
    {
        Route::get('transactions/pending', '\SedpMis\Transactions\Controllers\TransactionsController@pending');
        Route::get('transactions/tracker', '\SedpMis\Transactions\Controllers\TransactionsController@tracker');
        Route::get('transactions/history', '\SedpMis\Transactions\Controllers\TransactionsController@history');
    }
}
