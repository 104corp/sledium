<?php


namespace Sledium\Handlers;

use Sledium\Interfaces\ErrorReporterInterface;
use Psr\Log\LoggerInterface;

class DefaultErrorReporter implements ErrorReporterInterface
{
    /** @var string[] */
    protected $doNotReport = [];

    /** @var LoggerInterface  */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Reporting error
     * before report, MUST check the error should report, through shouldReport()
     * @param \Throwable $throwable
     * @return void
     */
    public function report(\Throwable $throwable)
    {
        if ($this->isShouldBeReported($throwable)) {
            $this->logger->error($throwable);
        }
    }

    /**
     * @param \Throwable $throwable
     * @return bool
     */
    public function isShouldBeReported(\Throwable $throwable): bool
    {
        foreach ($this->doNotReport as $class) {
            if ($throwable instanceof $class) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param string[] $exceptionClasses Exception class name list
     * @return void
     */
    public function setDoNotReport(array $exceptionClasses)
    {
        $this->doNotReport = $exceptionClasses;
    }

    /**
     * @return string[]
     */
    public function getDoNotReport(): array
    {
        return $this->doNotReport;
    }

    /**
     * @param string $exceptionClass
     */
    public function addDoNotReport(string $exceptionClass)
    {
        if (!in_array($exceptionClass, $this->doNotReport)) {
            $this->doNotReport[] = $exceptionClass;
        }
    }
}
