<?php


namespace Sledium\ServiceProviders;

use Illuminate\Support\Composer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Console\TableCommand;
use Illuminate\Queue\Console\FailedTableCommand;
use Illuminate\Queue\Console\WorkCommand as QueueWorkCommand;
use Illuminate\Queue\Console\RetryCommand as QueueRetryCommand;
use Illuminate\Database\Console\Seeds\SeedCommand;
use Illuminate\Database\Console\Seeds\SeederMakeCommand;
use Illuminate\Database\Console\Migrations\MigrateCommand;
use Illuminate\Database\Console\Migrations\MigrateMakeCommand;
use Illuminate\Cache\Console\CacheTableCommand;
use Illuminate\Cache\Console\ClearCommand as CacheClearCommand;
use Illuminate\Cache\Console\ForgetCommand as CacheForgetCommand;
use Illuminate\Queue\Console\ListenCommand as QueueListenCommand;
use Illuminate\Queue\Console\RestartCommand as QueueRestartCommand;
use Illuminate\Queue\Console\ListFailedCommand as ListFailedQueueCommand;
use Illuminate\Queue\Console\FlushFailedCommand as FlushFailedQueueCommand;
use Illuminate\Queue\Console\ForgetFailedCommand as ForgetFailedQueueCommand;
use Illuminate\Database\Console\Migrations\ResetCommand as MigrateResetCommand;
use Illuminate\Database\Console\Migrations\StatusCommand as MigrateStatusCommand;
use Illuminate\Database\Console\Migrations\InstallCommand as MigrateInstallCommand;
use Illuminate\Database\Console\Migrations\RefreshCommand as MigrateRefreshCommand;
use Illuminate\Database\Console\Migrations\RollbackCommand as MigrateRollbackCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleFinishCommand;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Sledium\Console\Commands\ConsoleMakeCommand;
use Sledium\Console\Commands\JobMakeCommand;
use Sledium\Container;
use Sledium\Handlers\DefaultErrorRenderer;
use Sledium\Handlers\DefaultErrorReporter;
use Sledium\Handlers\IlluminateExceptionHandler;

class ConsoleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $commands = [
        'Migrate' => 'command.migrate',
        'MigrateInstall' => 'command.migrate.install',
        'MigrateRefresh' => 'command.migrate.refresh',
        'MigrateReset' => 'command.migrate.reset',
        'MigrateRollback' => 'command.migrate.rollback',
        'MigrateStatus' => 'command.migrate.status',
        'QueueFailed' => 'command.queue.failed',
        'QueueFlush' => 'command.queue.flush',
        'QueueForget' => 'command.queue.forget',
        'QueueListen' => 'command.queue.listen',
        'QueueRestart' => 'command.queue.restart',
        'QueueRetry' => 'command.queue.retry',
        'QueueWork' => 'command.queue.work',
        'ScheduleFinish' => 'command.schedule.finish',
        'ScheduleRun' => 'command.schedule.run',
    ];

    /**
     * The commands to be registered.
     *
     * @var array
     */
    protected $devCommands = [
        'MigrateMake' => 'command.migrate.make',
        'ConsoleMake' => 'command.console.make',
        'JobMake' => 'command.job.make',
        'QueueFailedTable' => 'command.queue.failed-table',
        'QueueTable' => 'command.queue.table',
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerServices();
        $this->registerCommands();
    }

    protected function registerServices()
    {
        $this->app->singleton('composer', function ($app) {
            return new Composer($app['files'], $app->basePath());
        });

        if (!$this->app->has('httpErrorRenderer')) {
            $this->app['httpErrorRenderer'] = function (Container $container) {
                return new DefaultErrorRenderer();
            };
        }

        if (!$this->app->has('errorReporter')) {
            $this->app['errorReporter'] = function (Container $container) {
                $reporter = new DefaultErrorReporter($container->get('Psr\Log\LoggerInterface'));
                $doNotReport = $container['settings']['doNotReport'] ?? [];
                $reporter->setDoNotReport($doNotReport);
                return $reporter;
            };
        }

        $this->app->singleton(Schedule::class, function ($container) {
            return new Schedule;
        });

        $this->app['Illuminate\Contracts\Debug\ExceptionHandler'] = function (Container $container) {
            /** @var DefaultErrorRenderer $errorRenderer */
            $errorRenderer = $container->make('httpErrorRenderer');
            /** @var DefaultErrorReporter $errorReporter */
            $errorReporter = $container->make('errorReporter');
            $handler = new IlluminateExceptionHandler($errorRenderer, $errorReporter);
            if (is_callable([$handler, 'setDefaultRenderContentType'])) {
                call_user_func([$handler, 'setDefaultRenderContentType'], 'text/html');
            }
            return $handler;
        };
    }

    /**
     * Register the given commands.
     *
     * @return void
     */
    protected function registerCommands()
    {
        $commands = array_merge($this->commands, $this->devCommands);
        foreach ($commands as $command => $commandName) {
            call_user_func([$this, "register{$command}Command"], $commandName);
        }
        $this->commands(array_values($commands));
    }


    protected function registerMigrateCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new MigrateCommand($container['migrator']);
        });
    }

    protected function registerMigrateInstallCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new MigrateInstallCommand($container['migration.repository']);
        });
    }

    protected function registerMigrateMakeCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            $creator = $container['migration.creator'];
            $composer = $container['composer'];
            return new MigrateMakeCommand($creator, $composer);
        });
    }

    protected function registerConsoleMakeCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new ConsoleMakeCommand($container['files']);
        });
    }

    protected function registerJobMakeCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new JobMakeCommand($container['files']);
        });
    }

    protected function registerMigrateRefreshCommand($name)
    {
        $this->app->singleton($name, function () {
            return new MigrateRefreshCommand;
        });
    }

    protected function registerMigrateResetCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new MigrateResetCommand($container['migrator']);
        });
    }

    protected function registerMigrateRollbackCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new MigrateRollbackCommand($container['migrator']);
        });
    }

    protected function registerMigrateStatusCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new MigrateStatusCommand($container['migrator']);
        });
    }

    protected function registerQueueFailedCommand($name)
    {
        $this->app->singleton($name, function () {
            return new ListFailedQueueCommand;
        });
    }

    protected function registerQueueForgetCommand($name)
    {
        $this->app->singleton($name, function () {
            return new ForgetFailedQueueCommand;
        });
    }

    protected function registerQueueFlushCommand($name)
    {
        $this->app->singleton($name, function () {
            return new FlushFailedQueueCommand;
        });
    }
    protected function registerQueueListenCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new QueueListenCommand($container['queue.listener']);
        });
    }

    protected function registerQueueRestartCommand($name)
    {
        $this->app->singleton($name, function () {
            return new QueueRestartCommand;
        });
    }

    protected function registerQueueRetryCommand($name)
    {
        $this->app->singleton($name, function () {
            return new QueueRetryCommand;
        });
    }

    protected function registerQueueWorkCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new QueueWorkCommand($container['queue.worker']);
        });
    }

    protected function registerQueueFailedTableCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new FailedTableCommand($container['files'], $container['composer']);
        });
    }

    protected function registerQueueTableCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new TableCommand($container['files'], $container['composer']);
        });
    }

    protected function registerSeederMakeCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new SeederMakeCommand($container['files'], $container['composer']);
        });
    }

    protected function registerSeedCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new SeedCommand($container['db']);
        });
    }

    protected function registerScheduleFinishCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new ScheduleFinishCommand($container->get(Schedule::class));
        });
    }

    protected function registerScheduleRunCommand($name)
    {
        $this->app->singleton($name, function (Container $container) {
            return new ScheduleRunCommand($container->get(Schedule::class));
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array_merge(array_values($this->commands), array_values($this->devCommands));
    }
}
