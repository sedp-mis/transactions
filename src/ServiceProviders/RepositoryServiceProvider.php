<?php

namespace SedpMis\Transactions\ServiceProviders;

class RepositoryServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $repositories = [
            'DocumentSignatory',
            'DocumentTypeSignatory',
            'JournalHead',
            'Signatory',
            'Transaction',
        ];

        foreach ($repositories as $i => $name) 
        {
            $this->app->bind(
                "SedpMis\\Transactions\\Repositories\\{$name}\\{$name}RepositoryInterface",
                "SedpMis\\Transactions\\Repositories\\{$name}\\{$name}RepositoryEloquent"
                // true
            );
        }
    }
}