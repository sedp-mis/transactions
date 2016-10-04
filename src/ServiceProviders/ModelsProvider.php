<?php

namespace SedpMis\Transactions\ServiceProviders;

class ModelsProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $models = [
            'Transaction',
            'Signatory',
        ];

        foreach ($models as $model) {
            $this->app->bind(
                "SedpMis\\Transactions\\Models\\Interfaces\\{$model}Interface",
                "SedpMis\\Transactions\\Models\\{$model}"
            );
        }
    }
}
