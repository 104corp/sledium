<?php


namespace Sledium;

use Sledium\Exceptions\HttpBadRequestException;
use Sledium\Exceptions\HttpClientException;
use Slim\App as SlimApp;
use FastRoute\Dispatcher;
use Illuminate\Support\Collection;
use Sledium\Exceptions\HttpNotFoundException;
use Sledium\ServiceProviders\HttpServiceProvider;
use Sledium\Exceptions\HttpMethodNotAllowedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\InvalidMethodException;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class App
 * @package Sledium
 */
class App extends SlimApp
{
    /**
     * @var array default settings
     */
    private $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'autoHandleOptionsMethod' => true,
        'routerCacheFile' => false,
        'defaultContentType' => 'text/html',
        'doNotReport' => [
            HttpClientException::class,
        ],
    ];

    /**
     * App constructor.
     * @param Container $container
     */
    public function __construct(Container $container = null)
    {
        if (null === $container) {
            $container = new Container($this->guessBasePath());
        }
        $container->setIsRunningInConsole(false);
        parent::__construct($container);
        $this->setPhpNativeErrorHandlers();
        $this->registerServices();
    }

    protected function guessBasePath(): string
    {
        if (defined('APP_BASE_PATH')) {
            return APP_BASE_PATH;
        }
        list($one, $two, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return isset($caller['file']) ? dirname(dirname($caller['file'])) : getcwd();
    }

    /**
     * Register HTTP app needed services
     */
    protected function registerServices()
    {
        $container = $this->getContainer();
        $container->instance('sledium', $this);
        $this->getContainer()->singleton('config', function () {
            return new Config($this->getContainer()->configPath());
        });
        $this->getContainer()->singleton('settings', function () {
            /** @var Collection $setting */
            $setting = $this->getContainer()->get('config')->get('settings', new Collection([]));
            $setting = new Collection(array_merge($this->defaultSettings, $setting->toArray()));
            $this->getContainer()->get('config')->set('settings', $setting);
            return $setting;
        });

        $this->getContainer()->registerConfiguredProviders();
        $this->getContainer()->register(HttpServiceProvider::class);
        $this->getContainer()->alias('request', 'Psr\Http\Message\ServerRequestInterface');
        $this->getContainer()->alias('request', 'Slim\Http\Request');
        $this->getContainer()->alias('response', 'Psr\Http\Message\ResponseInterface');
        $this->getContainer()->alias('response', 'Slim\Http\Response');
    }

    /**
     * @return Container
     */
    public function getContainer(): Container
    {
        return parent::getContainer();
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getContainer()->boot();
        return parent::process($request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getContainer()->boot();
        $routeInfo = $request->getAttribute('routeInfo');
        /** @var \Slim\Interfaces\RouterInterface $router */
        $router = $this->getContainer()->get('router');

        try {
            $method = $request->getMethod();
        } catch (InvalidMethodException $e) {
            return $this->processInvalidMethod($request, $response);
        }

        if (null === $routeInfo || ($routeInfo['request'] !== [$method, (string)$request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo', [0 => -1]);
        }
        return $this->processRouteInfo($request, $response, $routeInfo);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function processInvalidMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->getContainer()->get('router');
        if (is_callable([$request->getUri(), 'getBasePath']) && is_callable([$router, 'setBasePath'])) {
            $router->setBasePath($request->getUri()->getBasePath());
        }
        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->getAttribute('routeInfo', [0 => -1]);
        return $this->processRouteInfo($request, $response, $routeInfo);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $routeInfo
     * @return ResponseInterface
     */
    protected function processRouteInfo(ServerRequestInterface $request, ResponseInterface $response, array $routeInfo)
    {
        $router = $this->getContainer()->get('router');
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $router->lookupRoute($routeInfo[1]);
            return $route->run($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->processMethodNotAllowed($request, $response, $routeInfo[1]);
        } elseif ($routeInfo[0] === Dispatcher::NOT_FOUND) {
            return $this->handleException(new HttpNotFoundException(), $request, $response);
        }
        return $this->handleException(new HttpBadRequestException(), $request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param array $allowedMethods
     * @return mixed|ResponseInterface
     */
    protected function processMethodNotAllowed(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $allowedMethods
    ) {
        $settings = $this->getContainer()['settings'];

        if ($request->getMethod() === 'OPTIONS'
            && $settings['autoHandleOptionsMethod']
            && $this->getContainer()->has('optionsMethodHandler')
            && is_callable($optionsMethodHandler = $this->getContainer()->get('optionsMethodHandler'))
        ) {
            return call_user_func_array(
                $optionsMethodHandler,
                [$request, $response, $allowedMethods]
            );
        }

        $allowedMethods = implode(', ', $allowedMethods);
        return $this->handleException(
            new HttpMethodNotAllowedException(
                'Method not allowed. Must be one of: ' . $allowedMethods,
                ['Allow' => $allowedMethods]
            ),
            $request,
            $response
        );
    }

    /**
     * Set php native error handlers
     */
    protected function setPhpNativeErrorHandlers()
    {
        $errorReporting = error_reporting();
        error_reporting(0);
        set_error_handler(
            function ($level, $message, $file = '', $line = 0) use ($errorReporting){
                if ($errorReporting & $level) {
                    error_clear_last();
                    throw new \ErrorException($message, 0, $level, $file, $line);
                }
                return true;
            }
        );
        set_exception_handler(
            function ($e) {
                $this->handleUncaughtException($e);
            }
        );
        register_shutdown_function(
            $shoutDownFunction = function () {
                $this->handleShutdown();
            }
        );
        $this->getContainer()->instance('shoutDownFunction', $shoutDownFunction);
    }

    /**
     * @param \Throwable $e
     */
    protected function handleUncaughtException(\Throwable $e)
    {
        restore_exception_handler();
        $container = $this->getContainer();
        $request = $container->has('request')
            ? $container->get('request')
            : Request::createFromEnvironment(new Environment($_SERVER));
        $response = $container->has('response')
            ? $container->get('response')
            : (new Response(200, new Headers(['Content-Type' => 'text/html; charset=UTF-8'])))
                ->withProtocolVersion($container->get('settings')['httpVersion']);
        if ($e instanceof \Exception) {
            $response = $this->handleException($e, $request, $response);
        } else {
            $response = $this->handlePhpError($e, $request, $response);
        }
        $this->respond($response);
    }

    /**
     * before finish php process hook
     */
    protected function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
            error_clear_last();
            $this->handleUncaughtException(
                new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
            );
        }
    }

    /**
     * @param int $type
     * @return bool
     */
    protected function isFatalError(int $type): bool
    {
        return in_array($type, [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE]);
    }

    /**
     * @param \Exception $e
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    protected function handleException(\Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->handlePhpError($e, $request, $response);
    }

    /**
     * @param \Throwable $e
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     * @throws \Throwable
     */
    protected function handlePhpError(\Throwable $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->getContainer()->has('errorHandler')) {
            $callable = $this->getContainer()->get('errorHandler');
            return call_user_func_array($callable, [$request, $response, $e]);
        }
        throw $e;
    }
}
