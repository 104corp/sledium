<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Console\Scheduling\Schedule;
use Sledium\ConsoleApp;

class ErrorMakerConsoleApp extends ConsoleApp
{
    protected $commands = [
        ExceptionMakerCommand::class,
        ErrorMakerCommand::class,
        NativeErrorMakerCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command(DummyCommand::class)->everyMinute();
    }
}
