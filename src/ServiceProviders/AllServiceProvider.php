<?php

namespace SedpMis\Transactions\ServiceProviders;

class AllServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        // Register repository provider
        (new RepositoryServiceProvider($this->app))->register();
    }
}
