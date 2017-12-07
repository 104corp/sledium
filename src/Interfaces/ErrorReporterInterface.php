<?php


namespace Sledium\Interfaces;

interface ErrorReporterInterface
{
    /**
     * Reporting error
     * before report, MUST check the error should report, through shouldReport()
     * @param \Throwable $throwable
     * @return void
     */
    public function report(\Throwable $throwable);

    /**
     * @param \Throwable $throwable
     * @return bool
     */
    public function isShouldBeReported(\Throwable $throwable): bool;

    /**
     * @param string[] $exceptionClasses Exception class name list
     * @return void
     */
    public function setDoNotReport(array $exceptionClasses);

    /**
     * @return string[]
     */
    public function getDoNotReport(): array;

    /**
     * @param string $exceptionClass
     * @return void
     */
    public function addDoNotReport(string $exceptionClass);
}
