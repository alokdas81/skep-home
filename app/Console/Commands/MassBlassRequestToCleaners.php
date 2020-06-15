<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MassBlassRequestToCleaners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'booking:mass-blass';    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find cleaners for Mass Blass';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {         
        
        app()->call('App\Http\Controllers\Api\v1\CronController@sendMassBlassRequest');

    }
}
