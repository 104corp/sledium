<?php


namespace Apim\Framework;

use Apim\Framework\ServiceProviders\HttpServiceProvider;
use Illuminate\Support\Collection;
use Slim\App as SlimApp;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Apim\Framework\Handlers\IlluminateExceptionHandler;

/**
 * Class App
 * @package Apim\Framework
 */
class App extends SlimApp
{
    private $defaultSettings = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'routerCacheFile' => false,
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
        parent::__construct($container);
        $this->setErrorHandlers();
        $this->registerServices();
    }

    protected function guessBasePath(): string
    {
        list($one, $two, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        return dirname(dirname($caller['file']));
    }

    protected function registerServices()
    {
        $container = $this->getContainer();
        $container->instance('slim', $this);
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
        if (!$container->has('exception.handler')) {
            $container['exception.handler'] = function ($container) {
                return new IlluminateExceptionHandler($container);
            };
        }
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
        return parent::__invoke($request, $response);
    }

    protected function setErrorHandlers()
    {
        error_reporting(-1);
        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                error_clear_last();
                throw new \ErrorException($message, 0, $level, $file, $line);
            }
        });
        set_exception_handler(function ($e) {
            $this->handleUncaughtException($e);
        });
        register_shutdown_function(function () {
            $this->handleShutdown();
        });
    }

    /**
     * @param \Throwable $e
     */
    protected function handleUncaughtException(\Throwable $e)
    {
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
}
