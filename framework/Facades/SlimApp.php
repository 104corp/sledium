<?php

namespace Apim\Framework\Facades;

use Illuminate\Support\Facades\Facade;

class SlimApp extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'slim';
    }
}
