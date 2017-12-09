<?php


namespace Sledium\Traits;

use Sledium\Container;
use Symfony\Component\Debug\Exception\FatalErrorException;

/**
 * Trait ErrorHandleAwareTrait
 * @package Sledium
 * @method void handleUncaughtException(\Throwable $e);
 * @method Container getContainer();
 */
trait AppErrorHandleAwareTrait
{
    static protected $errorReporting = null;
    /**
     * Set php native error handlers
     */
    protected function setPhpNativeErrorHandlers()
    {
        error_reporting(-1);
        set_error_handler(function ($level, $message, $file = '', $line = 0) {
            if (error_reporting() & $level) {
                error_clear_last();
                throw new FatalErrorException($message, 0, $level, $file, $line);
            }
        });
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
     * before finish php process hook
     */
    protected function handleShutdown()
    {
        if (!is_null($error = error_get_last()) && $this->isFatalError($error['type'])) {
            error_clear_last();
            $this->handleUncaughtException(
                new FatalErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])
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
}
