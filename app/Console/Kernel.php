<?php

namespace App\Console;

use App\Jobs\RetrieveContent;
use App\Jobs\RetrieveLinks;
use App\Jobs\SendTelegramMessages;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job(new RetrieveLinks)
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        $schedule->job(new RetrieveContent)
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->job(new SendTelegramMessages)
            ->everyFiveMinutes()
            ->withoutOverlapping();

        $schedule->command('horizon:snapshot')
            ->everyFiveMinutes();

        $schedule->command('backup:clean')
            ->daily()
            ->at('01:00');

        $schedule->command('backup:run')
            ->daily()
            ->at('02:00');

        $schedule->command('backup:monitor')
            ->daily()
            ->at('03:00');
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
