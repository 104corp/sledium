<?php


namespace Apim\Framework;

use Apim\Framework\Handlers\IlluminateExceptionHandler;
use Apim\Framework\Registrars\ConsoleAliasRegistrar;
use Apim\Framework\Registrars\ConsoleServicesRegistrar;
use Apim\Framework\ServiceProviders\ConsoleServiceProvider;
use Illuminate\Console\Application as IlluminateApplication;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as IlluminateConsoleKernel;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptionHandlerInterface;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApp implements IlluminateConsoleKernel
{

    /** @var  Container */
    protected $container;
    /** @var  IlluminateApplication */
    protected $illuminateApplication;

    /**
     * @var array
     */
    protected $commands = [];
    /** @var OutputInterface */
    private $output;
    /** @var InputInterface */
    private $input;


    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->registerErrorHandling();
        $this->registerServices();
        $this->container->booted(function () {
            $this->defineConsoleSchedule();
        });
        $this->container->activeIlluminateFacades();
    }


    /**
     * @return Container
     */
    public function getContainer()
    {
        return $this->container;
    }


    /**
     * @return IlluminateApplication
     */
    public function getIlluminateApplication()
    {
        if (null === $this->illuminateApplication) {
            $this->illuminateApplication =
                (new IlluminateApplication(
                    $this->container,
                    $this->container->make(Dispatcher::class),
                    $this->container->version()
                ))->resolveCommands($this->commands);
        }
        return $this->illuminateApplication;
    }

    /**
     * Handle an incoming console command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    public function handle($input, $output = null) : int
    {
        try {
            $input = $input ?: new ArgvInput();
            $output = $output ?: new ConsoleOutput();
            $this->output = $output;
            $this->container->boot();
            return (int)$this->getIlluminateApplication()->run($input, $output);
        } catch (\Throwable $e) {
            $this->handleError($output, $e);
            return 1;
        }
    }

    public function run($input = null, $output = null)
    {
        return $this->handle($input, $output);
    }

    /**
     * @param string $command
     * @param array $parameters
     * @param null $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], $outputBuffer = null)
    {
        return $this->getIlluminateApplication()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue an Artisan console command by name.
     *
     * @param  string $command
     * @param  array $parameters
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function queue($command, array $parameters = [])
    {
        throw new \RuntimeException('Queueing commands is not supported here.');
    }

    /**
     * Get all of the commands registered with the console.
     *
     * @return array
     */
    public function all()
    {
        return $this->getIlluminateApplication()->all();
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {

        return $this->getIlluminateApplication()->output();
    }

    public function schedule(Schedule $schedule)
    {
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        if (null === $this->output) {
            $this->output= new ConsoleOutput();
        }
        return $this->output;
    }

    /**
     * @return InputInterface
     */
    public function getInput()
    {
        if (null === $this->input) {
            $this->input = new ArgvInput();
        }
        return $this->input;
    }

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function defineConsoleSchedule()
    {
        $this->container->instance(Schedule::class, $schedule = new Schedule());
        $this->schedule($schedule);
    }

    protected function reportException($e)
    {
        $this->container[IlluminateExceptionHandlerInterface::class]->report($e);
    }

    protected function renderException($output, \Exception $e)
    {
        $this->container[IlluminateExceptionHandlerInterface::class]->renderForConsole($output, $e);
    }

    /**
     * Set the error handling for the application.
     *
     * @return void
     */
    protected function registerErrorHandling()
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
     * Handle the application shutdown routine.
     *
     * @return void
     */
    protected function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
            $this->handleUncaughtException(
                new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
            );
        }
    }

    protected function registerServices()
    {
        $this->getContainer()->singleton('config', function () {
            return new Config($this->getContainer()->configPath());
        });
        $this->getContainer()->singleton('settings', function () {
            return $this->getContainer()->get('config')->get('settings');
        });
        $this->getContainer()->registerConfiguredProviders();
        $this->getContainer()->register(ConsoleServiceProvider::class);
        if (!$this->container->has('exception.handler')) {
            $this->container['exception.handler'] = function ($container) {
                return new IlluminateExceptionHandler($container);
            };
        }
    }

    /**
     * Determine if the error type is fatal.
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
     * Handle an uncaught exception instance.
     * @param \Throwable $e
     */
    protected function handleUncaughtException(\Throwable $e)
    {
        $this->handleError($this->getOutput(), $e);
    }

    protected function handleError(OutputInterface $output, \Throwable $e)
    {
        $container = $this->getContainer();
        $errorHandler = $container->has('errorHandler') ? $container->get('errorHandler')
            : function (\Exception $e, OutputInterface $output) {
                $this->getIlluminateApplication()->renderException($e, $output);
            };
        $phpErrorHandler = $container->has('phpErrorHandler') ? $container->get('phpErrorHandler')
            : function (\Throwable $e, OutputInterface $output) use ($errorHandler) {
                $errorHandler($this->transformToErrorException($e), $output);
            };

        if ($e instanceof \Exception) {
            $errorHandler($e, $output);
        } else {
            $phpErrorHandler($e, $output);
        }
    }

    private function transformToErrorException(\Throwable $e):\ErrorException
    {
        $errorException = new \ErrorException($e->getMessage(), $e->getCode(), E_ERROR, $e->getFile(), $e->getLine());
        $reflectionClass = new \ReflectionClass(\Exception::class);
        $traceProperty = $reflectionClass->getProperty('trace');
        $traceProperty->setAccessible(true);
        $traceProperty->setValue($errorException, $e->getTrace());
        $traceProperty->setAccessible(false);
        return $errorException;
    }
}
