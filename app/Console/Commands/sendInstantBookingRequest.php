<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class sendInstantBookingRequest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'instant-booking:request';    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Before  instant booking becomes orphan it will try again radius search and send push to cleaner if its not already called earlier';

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
        
        app()->call('App\Http\Controllers\Api\v1\CronController@sendInstantBookingRequest');

    }
}
