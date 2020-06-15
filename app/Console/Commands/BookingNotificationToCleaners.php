<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BookingNotificationToCleaners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'booking:notification';    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send notification to cleaner before service start';

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
        
        app()->call('App\Http\Controllers\Api\v1\CronController@sendNotification');

    }
}
