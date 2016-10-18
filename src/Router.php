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
        Route::get('transactions', '\SedpMis\Transactions\Controllers\TransactionsController@index');
        Route::get('transactions/pending', '\SedpMis\Transactions\Controllers\TransactionsController@pending');
        Route::get('transactions/approved', '\SedpMis\Transactions\Controllers\TransactionsController@approved');
        Route::get('transactions/tracker', '\SedpMis\Transactions\Controllers\TransactionsController@tracker');
        Route::get('transactions/history', '\SedpMis\Transactions\Controllers\TransactionsController@history');
        Route::get('transactions/{transaction}', '\SedpMis\Transactions\Controllers\TransactionsController@show');
        Route::get('transactions/{transaction}/signatories', 'SedpMis\Transactions\Controllers\TransactionSignatoriesController@index');
        Route::get('transactions/{transaction}/document_list', 'SedpMis\Transactions\Controllers\TransactionDocumentsController@documentList');
    }
}
