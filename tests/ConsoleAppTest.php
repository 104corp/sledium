<?php


namespace Sledium\Tests;

use PHPUnit\Framework\TestCase;
use Sledium\ConsoleApp;
use Sledium\Container;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\BufferedOutput;

class ConsoleAppTest extends TestCase
{

    /**
     * @test
     */
    public function basicConsoleCanRun()
    {
        $container = new Container(__DIR__ . '/Fixtures/resources/ConsoleAppTest');
        $input = new ArgvInput([]);
        $output = new BufferedOutput();
        (new ConsoleApp($container))->run($input, $output);
        $this->assertRegExp("/Sledium Console App/", $output->fetch());
    }
}
