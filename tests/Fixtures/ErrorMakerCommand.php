<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Console\Command;

class ErrorMakerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-maker:error';


    protected $description = 'Error maker';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        throw new \Error();
    }
}
