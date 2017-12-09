<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Console\Command;

class ExceptionMakerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-maker:exception';


    protected $description = 'Exception maker';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        throw new \Exception();
    }
}
