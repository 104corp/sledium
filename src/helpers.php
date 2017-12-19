<?php
/**
 * Add for some laravel service provider
 */
use Sledium\Container;

if (!function_exists('config')) {
    function config($key = null, $default = null)
    {
        /**
         * @var Illuminate\Contracts\Config\Repository $config
         */
        $config = Container::getInstance()['config'];
        if (is_null($key)) {
            return $config;
        }
        if (is_array($key)) {
            return $config->set($key);
        }
        return $config->get($key, $default);
    }
}
if (!function_exists('base_path')) {
    function base_path($path = '')
    {
        return Container::getInstance()->basePath() . ($path ? DIRECTORY_SEPARATOR . $path : $path);
    }
}
