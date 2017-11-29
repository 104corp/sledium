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
        $this->registerRouter();
        $this->registerSlimDefault();
    }

    protected function registerRouter()
    {
        if (!isset($this->app['router'])) {
            $this->app['router'] = function (Container $container) {
                $routerCacheFile = false;
                if (isset($container->get('settings')['routerCacheFile'])) {
                    $routerCacheFile = $container->get('settings')['routerCacheFile'];
                }

                $router = (new Router)->setCacheFile($routerCacheFile);
                if (method_exists($router, 'setContainer')) {
                    $router->setContainer($container);
                }
                return $router;
            };
        }
    }

    protected function registerSlimDefault()
    {
        (new DefaultServicesProvider)->register($this->app);
    }
}
