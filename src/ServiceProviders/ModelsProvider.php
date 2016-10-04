<?php

namespace SedpMis\Transactions\ServiceProviders;

class ModelsProvider extends \Illuminate\Support\ServiceProvider
{
    protected $modelNamespace = 'SedpMis\\Transactions\\Models\\';

    public function register()
    {
        $models = [
            'Transaction',
            'TransactionApproval',
            'Signatory',
        ];

        foreach ($models as $model) {
            $this->app->bind(
                "SedpMis\\Transactions\\Models\\Interfaces\\{$model}Interface",
                "{$this->modelNamespace}{$model}"
            );
        }
    }
}
