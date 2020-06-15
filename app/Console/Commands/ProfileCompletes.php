<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProfileCompletes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'profile:complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send email to cleaners who didn;t complete their profile';

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
        //$bookings = new Bookings();
        //$transferMoney = $bookings->transferMoneyToCleaners();
        //exit("All code executed successfully!!");

        app()->call('App\Http\Controllers\Api\v1\CronController@profileCompleteEmailNotification');

       
    }
}
