<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class OrphanBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    protected $signature = 'booking:orphan';    

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'If booking is not confirmed by any cleaner with in certian period/rance then booking is mark as orphan and not visible to homeowner anymore';

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
        
        app()->call('App\Http\Controllers\Api\v1\CronController@makeBookingToOrphan');

    }
}
