<?php


namespace Sledium\Console\Commands;

use Illuminate\Console\GeneratorCommand as IlluminateGeneratorCommand;
use Illuminate\Support\Str;

abstract class GeneratorCommand extends IlluminateGeneratorCommand
{
    const STUBS_PATH = __DIR__.'/../../../resources/Console/Commands/stubs';
    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return $this->laravel->basePath().'/app'.'/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Replace the namespace for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            ['DummyNamespace', 'DummyRootNamespace'],
            [$this->getNamespace($name), $this->rootNamespace()],
            $stub
        );

        return $this;
    }
}
