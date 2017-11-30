<?php


namespace Apim\Framework\ServiceProviders;

use Apim\Framework\Container;
use Illuminate\Support\ServiceProvider;
use Slim\DefaultServicesProvider;
use Slim\Router;

class HttpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerSlimDefault();
    }


    protected function registerSlimDefault()
    {
        (new DefaultServicesProvider)->register($this->app);
    }
}
