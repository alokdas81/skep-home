<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [        
        Commands\sendInstantBookingRequest::class,
        Commands\ChargeCreateForAdvanceBooking::class,
        Commands\OrphanBooking::class,
        Commands\TransferMoneyToCleaners::class,
        Commands\ProfileCompletes::class,
        Commands\BookingNotificationToCleaners::class,
        Commands\MassBlassRequestToCleaners::class,
        Commands\sendHomeOwnerFavCleanerNotification::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
       $schedule->command('instant-booking:request')->everyMinute();          

        $schedule->command('booking:charge')->dailyAt('01:00');   //original
      // $schedule->command('booking:charge')->everyMinute();   // for testing purpose;

        $schedule->command('booking:orphan')->everyMinute();          

       // transfer cleaner referral_balance to cleaner stripe account once in a week
        $schedule->command('stripe:transfer-money')->weeklyOn(5, '01:00');
       //$schedule->command('stripe:transfer-money')->everyMinute();
      
        $schedule->command('profile:complete')->weeklyOn(5, '01:00');  

        $schedule->command('booking:notification')->everyMinute();   

        $schedule->command('booking:mass-blass')->everyMinute();

        $schedule->command('booking:homeowner-favcleaner-notification')->everyMinute();


    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
