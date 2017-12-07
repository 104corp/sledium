<?php


namespace Sledium\Interfaces;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface ErrorHandlerInterface
{
    /**
     * Handle HTTP App errors
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param \Throwable $exception
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        \Throwable $exception
    ): ResponseInterface;
}
