<?php


namespace Sledium;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class MiddlewareAbstract
{
    use ContainerAwareTrait;
    abstract public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response,
        callable $next
    ): ResponseInterface;
}
