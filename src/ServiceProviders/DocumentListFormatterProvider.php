<?php

namespace SedpMis\Transactions\ServiceProviders;

class DocumentListFormatterProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Register bindings for documentListFormatter inteface
        $this->app->bind(
            '\SedpMis\Transactions\Interfaces\DocumentListFormatterInterface',
            '\SedpMis\Transactions\TransactionDocuments\DefaultDocumentListFormatter'
        );
    }
}
