<?php


namespace Sledium;

use Slim\App as SlimApp;
use FastRoute\Dispatcher;
use Illuminate\Support\Collection;
use Sledium\Exceptions\HttpNotFoundException;
use Sledium\ServiceProviders\HttpServiceProvider;
use Sledium\Exceptions\HttpMethodNotAllowedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class App
 * @package Sledium
 */
class App extends SlimApp
{
    /**
     * @var int php error reporting
     */
    private $errorReporting = -1;

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
        list($one, $two, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return dirname(dirname($caller['file']));
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
     * @throws HttpMethodNotAllowedException
     * @throws HttpNotFoundException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response)
    {
        $this->getContainer()->boot();
        $routeInfo = $request->getAttribute('routeInfo');
        /** @var \Slim\Interfaces\RouterInterface $router */
        $router = $this->getContainer()->get('router');

        if (null === $routeInfo || ($routeInfo['request'] !== [$request->getMethod(), (string)$request->getUri()])) {
            $request = $this->dispatchRouterAndPrepareRoute($request, $router);
            $routeInfo = $request->getAttribute('routeInfo');
        }
        if ($routeInfo[0] === Dispatcher::FOUND) {
            $route = $router->lookupRoute($routeInfo[1]);
            return $route->run($request, $response);
        } elseif ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->processMethodNotAllowed(
                $request,
                $response,
                $routeInfo[1]
            );
        }
        return $this->handleException(new HttpNotFoundException(), $request, $response);
    }

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed|ResponseInterface
     */
    protected function processInvalidMethod(ServerRequestInterface $request, ResponseInterface $response)
    {
        $router = $this->getContainer()->get('router');
        if (is_callable([$request->getUri(), 'getBasePath'])
            && is_callable([$router, 'setBasePath'])
        ) {
            $router->setBasePath($request->getUri()->getBasePath());
        }
        $request = $this->dispatchRouterAndPrepareRoute($request, $router);
        $routeInfo = $request->getAttribute(
            'routeInfo',
            [0 => Dispatcher::NOT_FOUND]
        );
        if ($routeInfo[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return $this->processMethodNotAllowed(
                $request,
                $response,
                $routeInfo[1]
            );
        }
        return $this->handleException(new HttpNotFoundException(), $request, $response);
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
        $setting = $this->getContainer()['settings'];
        if ($request->getMethod() === 'OPTIONS'
            && isset($setting['autoHandleOptionsMethod'])
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
                'Allowed methods: ' . $allowedMethods,
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
        $this->errorReporting = error_reporting();
        error_reporting(0);
        set_error_handler(
            function ($level, $message, $file = '', $line = 0) {
                if ($this->errorReporting & $level) {
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
            function () {
                $this->handleShutdown();
            }
        );
    }

    /**
     * @param \Throwable $e
     */
    protected function handleUncaughtException(\Throwable $e)
    {
        restore_exception_handler();
        $container = $this->getContainer();
        $request = $container->get('request');
        $response = $container->get('response');
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
        $errorCodes = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE];
        if (defined('FATAL_ERROR')) {
            $errorCodes[] = FATAL_ERROR;
        }
        return in_array($type, $errorCodes);
    }

    /**
     * @param \Exception $e
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return mixed
     * @throws \Exception
     */
    protected function handleException(\Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    {
        return $this->handlePhpError($e, $request, $response);
    }

    /**
     * Call relevant handler from the Container if needed. If it doesn't exist,
     * then just re-throw.
     *
     * @param  \Throwable $e
     * @param  ServerRequestInterface $request
     * @param  ResponseInterface $response
     * @return ResponseInterface
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
