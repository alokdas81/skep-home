<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class sendHomeOwnerFavCleanerNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'booking:homeowner-favcleaner-notification';    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = ' If select favorite cleaner rejects or does not respond within the defined timeframe. homewoner will get push';

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
        
        app()->call('App\Http\Controllers\Api\v1\CronController@sendHomeOwnerFavCleanerNotification');

    }
}
