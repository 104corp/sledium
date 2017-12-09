<?php


namespace Sledium\Tests\Fixtures;

use Illuminate\Console\Command;

class NativeErrorMakerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'error-maker:native';


    protected $description = 'Native error maker';


    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        include 'not_exists';
    }
}
