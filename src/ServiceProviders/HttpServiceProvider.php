<?php


namespace Sledium\ServiceProviders;

use Psr\Container\ContainerInterface;
use Illuminate\Support\ServiceProvider;
use Sledium\Exceptions\HttpClientException;
use Sledium\Handlers\DefaultErrorRenderer;
use Sledium\Handlers\DefaultErrorReporter;
use Sledium\Handlers\DefaultOptionsMethodHandler;
use Sledium\Handlers\DefaultErrorHandler;
use Sledium\Handlers\IlluminateExceptionHandler;
use Slim\CallableResolver;
use Slim\Handlers\Strategies\RequestResponse;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

class HttpServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->registerEnvironment($this->app);
        $this->registerRequest($this->app);
        $this->registerResponse($this->app);
        $this->registerRouter($this->app);
        $this->registerFoundHandler($this->app);
        $this->registerCallableResolver($this->app);
        $this->registerErrorHandler($this->app);
        $this->registerIlluminateExceptionHandler($this->app);
        $this->registerOptionsMethodHandler($this->app);
        $this->registerCoreProviders();
    }

    public function provides()
    {
        return [
            'environment',
            'request',
            'response',
            'router',
            'foundHandler',
            'callableResolver',
            'optionsMethodHandler',
            'errorHandler',
            'errorRenderer',
            'errorReporter',
            'Illuminate\Contracts\Debug\ExceptionHandler',
        ];
    }

    private function registerEnvironment(ContainerInterface $container)
    {
        if (!$container->has('environment')) {
            $container['environment'] = function () {
                return new Environment($_SERVER);
            };
        }
    }

    private function registerRequest(ContainerInterface $container)
    {
        if (!$container->has('request')) {
            $container['request'] = function (ContainerInterface $container) {
                return Request::createFromEnvironment($container->get('environment'));
            };
        }
    }

    private function registerResponse(ContainerInterface $container)
    {
        if (!$container->has('response')) {
            $container['response'] = function (ContainerInterface $container) {
                $headers = new Headers(['Content-Type' => 'text/html; charset=UTF-8']);
                $response = new Response(200, $headers);
                return $response->withProtocolVersion($container->get('settings')['httpVersion']);
            };
        }
    }

    private function registerRouter(ContainerInterface $container)
    {
        if (!$container->has('router')) {
            $container['router'] = function (ContainerInterface $container) {
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

    private function registerFoundHandler(ContainerInterface $container)
    {
        if (!$container->has('foundHandler')) {
            $container['foundHandler'] = function () {
                return new RequestResponse;
            };
        }
    }

    private function registerCallableResolver(ContainerInterface $container)
    {
        if (!$container->has('callableResolver')) {
            $container['callableResolver'] = function ($container) {
                return new CallableResolver($container);
            };
        }
    }

    private function registerErrorHandler(ContainerInterface $container)
    {
        if (!$container->has('errorHandler')) {
            $container['errorHandler'] = function (ContainerInterface $container) {
                $handler = new DefaultErrorHandler(
                    $container->get('errorRenderer'),
                    $container->get('errorReporter'),
                    $this->displayErrorDetails()
                );
                if (is_callable([$handler, 'setDefaultRenderContentType'])) {
                    call_user_func([$handler, 'setDefaultRenderContentType'], $this->defaultContentType());
                }
                return $handler;
            };
        }
        if (!$container->has('errorRenderer')) {
            $container['errorRenderer'] = function (ContainerInterface $container) {
                return new DefaultErrorRenderer();
            };
        }
        if (!$container->has('errorReporter')) {
            $container['errorReporter'] = function (ContainerInterface $container) {
                $reporter = new DefaultErrorReporter($container->get('Psr\Log\LoggerInterface'));
                $reporter->setDoNotReport($container['settings']['doNotReport']);
                return $reporter;
            };
        }
    }

    private function registerIlluminateExceptionHandler(ContainerInterface $container)
    {
        if (!$container->has('Illuminate\Contracts\Debug\ExceptionHandler')) {
            $container['Illuminate\Contracts\Debug\ExceptionHandler'] = function (ContainerInterface $container) {
                $handler = new IlluminateExceptionHandler(
                    $container->get('errorRenderer'),
                    $container->get('errorReporter')
                );
                if (is_callable([$handler, 'setDefaultRenderContentType'])) {
                    call_user_func([$handler, 'setDefaultRenderContentType'], $this->defaultContentType());
                }
                return $handler;
            };
        }
    }

    private function registerOptionsMethodHandler(ContainerInterface $container)
    {
        if (!$container->has('optionsMethodHandler')) {
            $container['optionsMethodHandler'] = function (ContainerInterface $container) {
                $handler = new DefaultOptionsMethodHandler();
                $handler->setDefaultRenderContentType($this->defaultContentType());
                return $handler;
            };
        }
    }


    private function registerCoreProviders()
    {
        foreach ([
                     'cache' => 'Illuminate\Cache\CacheServiceProvider',
                     'cache.store' => 'Illuminate\Cache\CacheServiceProvider',
                     'encrypter' => 'Illuminate\Encryption\EncryptionServiceProvider',
                     'db' => 'Illuminate\Database\DatabaseServiceProvider',
                     'db.connection' => 'Illuminate\Database\DatabaseServiceProvider',
                     'files' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'filesystem' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'filesystem.disk' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'filesystem.cloud' => 'Illuminate\Filesystem\FilesystemServiceProvider',
                     'hash' => 'Illuminate\Hashing\HashServiceProvider',
                     'log' => 'Sledium\ServiceProviders\LogServiceProvider',
                     'mailer' => 'Illuminate\Mail\MailServiceProvider',
                     'queue' => 'Illuminate\Queue\QueueServiceProvider',
                     'queue.connection' => 'Illuminate\Queue\QueueServiceProvider',
                     'queue.failer' => 'Illuminate\Queue\QueueServiceProvider',
                     'queue.listener' => 'Illuminate\Queue\QueueServiceProvider',
                     'redis' => 'Illuminate\Redis\RedisServiceProvider',
                 ] as $service => $provider) {
            $this->app->registerDeferredProvider($provider, $service);
        }
    }

    private function displayErrorDetails():bool
    {
        return (bool)$this->app->get('settings')['displayErrorDetails'];
    }

    private function defaultContentType():string
    {
        return (string)$this->app->get('settings')['defaultContentType'];
    }
}
