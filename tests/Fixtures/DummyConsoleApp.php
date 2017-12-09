<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Console\Scheduling\Schedule;
use Sledium\ConsoleApp;

class DummyConsoleApp extends ConsoleApp
{
    protected $commands = [
        DummyCommand::class
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DummyCommand::class)->everyMinute();
    }
}
