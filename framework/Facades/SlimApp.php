<?php

namespace Apim\Framework\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class SlimApp
 * @package Apim\Framework\Facades
 */
class SlimApp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'slim';
    }
}
