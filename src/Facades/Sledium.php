<?php

namespace Sledium\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Sledium
 * @package Sledium\Facades
 * @method static \Sledium\Container getContainer()
 * @method static \Slim\Interfaces\RouteInterface get(string $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteInterface post(string $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteInterface patch(string $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteInterface delete(string $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteInterface options(string $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteInterface any(string $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteInterface map(string[] $methods, $pattern, callable|string $callable)
 * @method static \Slim\Interfaces\RouteGroupInterface group(string $pattern, callable $callable)
 * @method static \Slim\App add(callable|string $callable)
 */
class Sledium extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sledium';
    }
}
