<?php


namespace Sledium;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

abstract class RouteActionAbstract
{
    use ContainerAwareTrait;
    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return string|ResponseInterface
     */
    abstract public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
