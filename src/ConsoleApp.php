<?php


namespace Sledium;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel as IlluminateConsoleKernel;
use Illuminate\Contracts\Events\Dispatcher;
use Sledium\Traits\AppErrorHandleAwareTrait;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Sledium\ServiceProviders\ConsoleServiceProvider;
use Symfony\Component\Debug\Exception\FatalThrowableError;

/**
 * Class ConsoleApp
 * @package Sledium
 */
class ConsoleApp implements IlluminateConsoleKernel
{
    use AppErrorHandleAwareTrait;
    /** @var  Container */
    protected $container;
    /** @var  Application */
    protected $application;

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
        $this->container->setIsRunningInConsole(true);
        $this->setPhpNativeErrorHandlers();
        $this->registerBaseBindings();
        $this->registerCommons();
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
     * @return Application
     */
    public function getIlluminateApplication()
    {
        if (null === $this->application) {
            $this->application =
                (new Application(
                    $this->container,
                    $this->container->make(Dispatcher::class),
                    $this->container->version()
                ))->resolveCommands($this->commands);
            $this->application->setName('Sledium Console App');
        }
        return $this->application;
    }

    /**
     * Handle an incoming console command.
     *
     * @param  InputInterface $input
     * @param  OutputInterface $output
     * @return int
     */
    public function handle($input, $output = null): int
    {
        try {
            $input = $input ?: new ArgvInput();
            $output = $output ?: new ConsoleOutput();
            $this->output = $output;
            $this->container->boot();
            return (int)$this->getIlluminateApplication()->run($input, $output);
        } catch (\Throwable $e) {
            $this->handleError($e, $output);
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
     * @param OutputInterface|null $outputBuffer
     * @return int
     */
    public function call($command, array $parameters = [], OutputInterface $outputBuffer = null)
    {
        $this->container->boot();
        if ($outputBuffer !== null) {
            $this->output = $outputBuffer;
        }
        return $this->getIlluminateApplication()->call($command, $parameters, $outputBuffer);
    }

    /**
     * Queue an Artisan console command by name.
     * @TODO
     * @param  string $command
     * @param  array $parameters
     * @return \Illuminate\Foundation\Bus\PendingDispatch
     */
    public function queue($command, array $parameters = [])
    {
        throw new \RuntimeException('Queueing commands is not supported.');
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

    /**
     * To Override
     * @param Schedule $schedule
     */
    protected function schedule(Schedule $schedule)
    {
    }

    /**
     * @return OutputInterface
     */
    public function getOutput()
    {
        if (null === $this->output) {
            $this->output = new ConsoleOutput();
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
        $this->schedule($this->container->get(Schedule::class));
    }

    protected function reportException(\Throwable $e)
    {
        $container = $this->getContainer();
        if (!$container->has('errorReporter')) {
            error_log($e);
        } else {
            $container->get('errorReporter')->report($e);
        }
    }

    protected function renderException(\Throwable $e, OutputInterface $output)
    {
        $container = $this->getContainer();
        if (!$container->has('consoleErrorRenderer')) {
            if ($e instanceof \Error) {
                $e = new FatalThrowableError($e);
            }
            $this->getIlluminateApplication()->renderException($e, $output);
        } else {
            $container->get('consoleErrorRenderer')->render($e, $output);
        }
    }

    /**
     * Handle an uncaught exception instance.
     * @param \Throwable $e
     */
    protected function handleUncaughtException(\Throwable $e)
    {
        restore_exception_handler();
        $this->handleError($e, $this->getOutput());
    }

    protected function handleError(\Throwable $e, OutputInterface $output)
    {
        $this->reportException($e);
        $this->renderException($e, $output);
    }

    protected function registerBaseBindings()
    {
        $this->getContainer()->singleton('config', function () {
            return new Config($this->getContainer()->configPath());
        });
        $this->getContainer()->singleton('settings', function () {
            return $this->getContainer()->get('config')->get('settings');
        });
    }

    /**
     * Register Common services and aliases
     */
    protected function registerCommons()
    {
        (new CommonServicesRegisterer($this->getContainer()))->register();
    }

    /**
     * Register Console app needed services
     */
    protected function registerServices()
    {
        $container = $this->getContainer();
        $container->registerDeferredProvider('Illuminate\Database\MigrationServiceProvider', 'migrator');
        $container->registerConfiguredProviders();
        $container->register(ConsoleServiceProvider::class);
    }
}
