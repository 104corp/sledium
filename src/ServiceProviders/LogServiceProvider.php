<?php


namespace Sledium\ServiceProviders;

use Illuminate\Log\Writer;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Monolog\Handler\AbstractHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger as Monolog;

class LogServiceProvider extends ServiceProvider
{
    /**
     * The Log levels.
     *
     * @var array
     */
    protected $levels = [
        'debug' => Monolog::DEBUG,
        'info' => Monolog::INFO,
        'notice' => Monolog::NOTICE,
        'warning' => Monolog::WARNING,
        'error' => Monolog::ERROR,
        'critical' => Monolog::CRITICAL,
        'alert' => Monolog::ALERT,
        'emergency' => Monolog::EMERGENCY,
    ];
    private $loggerConfig;

    public function register()
    {
        $this->app->singleton('log', function () {
            $writer = new Writer(new Monolog($this->getLoggerConfig()->get('channel', 'Sledium')));
            $handlers = $this->getLoggerConfig()->get('handlers', []);
            $hasHandler = false;
            $defaultLevel = $this->getLoggerConfig()->get('default_level', 'debug');
            foreach ($handlers as $handlerInfo) {
                if (isset($handlerInfo['handler'])) {
                    $this->pushHandler($writer, $handlerInfo['handler'], $handlerInfo['level']??$defaultLevel);
                    $hasHandler = true;
                }
            }
            if (!$hasHandler) {
                $writer->useFiles($this->getLogPath(), $defaultLevel);
            }
            return $writer;
        });
    }

    /**
     * @return void
     */
    public function boot()
    {

        if ($this->app->runningInConsole()) {
            $this->publishes(
                [
                    __DIR__ . '/../../resources/ServiceProviders/config/logger.php' =>
                        $this->app->configPath() . '/logger.php',
                ],
                'sledium-logger'
            );
        }
    }

    public function provides()
    {
        return ['log'];
    }

    protected function getLoggerConfig(): Collection
    {
        if (null === $this->loggerConfig) {
            /** @var \Illuminate\Contracts\Config\Repository $config */
            $config = $this->app['config'];
            if (empty($loggerConfig = $config->get('logger'))) {
                $loggerConfig = include __DIR__ . '/../../resources/ServiceProviders/config/logger.php';
            }
            $this->loggerConfig = new Collection($loggerConfig);
        }
        return $this->loggerConfig;
    }

    protected function pushHandler(Writer $writer, string $handler, string $level)
    {
        switch ($handler) {
            case 'files':
                $writer->useFiles($this->getLogPath(), $level);
                break;
            case 'daily_files':
                $writer->useDailyFiles(
                    $this->getLogPath(),
                    $this->getLoggerConfig()->get('max_files', 5),
                    $level
                );
                break;
            case 'syslog':
                $writer->useSyslog($writer->getMonolog()->getName(), $level);
                break;
            case 'error_log':
                $writer->useErrorLog($level);
                break;
            default:
                /** @var HandlerInterface $handler */
                $handler = $this->app->make($handler);
                if ($handler instanceof AbstractHandler) {
                    $handler->setLevel($this->parseToMonoloagLevel($level));
                }
                $writer->getMonolog()->pushHandler($handler);
                break;
        }
    }

    protected function parseToMonoloagLevel(string $level): int
    {
        return $this->levels[$level] ?? Monolog::DEBUG;
    }

    protected function getLogPath(): string
    {
        return $this->app->storagePath().'/logs/app.log';
    }
}
