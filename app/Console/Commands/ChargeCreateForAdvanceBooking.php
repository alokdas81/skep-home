<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ChargeCreateForAdvanceBooking extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'booking:charge';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'this is for to create charge before 48 hours of advance booking date';

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
        
        app()->call('App\Http\Controllers\Api\v1\CronController@processChargesHomeowners');

    }
}
