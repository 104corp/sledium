<?php


namespace Apim\Framework\Handlers;

use Apim\Framework\Container;
use Illuminate\Contracts\Debug\ExceptionHandler as IlluminateExceptionHandlerInterface;

abstract class AbstractErrorHandler
{

    /**
     * @var bool
     */
    protected $displayErrorDetails;

    /**
     * @var bool|string
     */
    protected $outputBuffering;

    /**
     * @var IlluminateExceptionHandlerInterface
     */
    protected $illuminateExceptionHandler;

    /**
     * AbstractErrorHandler constructor.
     * @param bool $displayErrorDetails
     * @param bool $outputBuffering
     */
    public function __construct($displayErrorDetails = false, $outputBuffering = false)
    {
        $this->displayErrorDetails = (bool) $displayErrorDetails;
        $this->outputBuffering = $outputBuffering;
    }

    /**
     * @return IlluminateExceptionHandlerInterface
     */
    protected function getIlluminateExceptionHandler()
    {
        if (null === $this->illuminateExceptionHandler) {
            $this->illuminateExceptionHandler = Container::getInstance()->get('exception.handler');
        }
        return $this->illuminateExceptionHandler;
    }

    /**
     * Write to the error log if displayErrorDetails is false
     *
     * @param \Exception|\Throwable $throwable
     *
     * @return void
     */
    protected function writeToErrorLog($throwable)
    {
        $this->getIlluminateExceptionHandler()->report($throwable);
    }

    /**
     * Render error as Text.
     *
     * @param \Exception|\Throwable $throwable
     *
     * @return string
     */
    protected function renderThrowableAsText($throwable)
    {
        $text = sprintf('Type: %s' . PHP_EOL, get_class($throwable));

        if ($code = $throwable->getCode()) {
            $text .= sprintf('Code: %s' . PHP_EOL, $code);
        }

        if ($message = $throwable->getMessage()) {
            $text .= sprintf('Message: %s' . PHP_EOL, htmlentities($message));
        }

        if ($file = $throwable->getFile()) {
            $text .= sprintf('File: %s' . PHP_EOL, $file);
        }

        if ($line = $throwable->getLine()) {
            $text .= sprintf('Line: %s' . PHP_EOL, $line);
        }

        if ($trace = $throwable->getTraceAsString()) {
            $text .= sprintf('Trace: %s', $trace);
        }

        return $text;
    }

    /**
     * Wraps the error_log function so that this can be easily tested
     *
     * @param $message
     */
    protected function logError($message)
    {
        $this->getIlluminateExceptionHandler()->report();
    }
}
