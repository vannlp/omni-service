<?php

namespace App\Console;

use App\Console\Commands\PushOrderDMSCommand;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;
use App\Console\Commands\TMCronCommand;
use App\Console\Commands\HubCronCommand;
use App\Console\Commands\OrderExportCronCommand;
use App\Console\Commands\RePostCdpCommand;
use App\Console\Commands\TempOrderExportCronCommand;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //\Laravelista\LumenVendorPublish\VendorPublishCommand::class,
        TMCronCommand::class,
        HubCronCommand::class,
        OrderExportCronCommand::class,
        PushOrderDMSCommand::class,
        TempOrderExportCronCommand::class,
        RePostCdpCommand::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('tm:cron')->everyMinute()->withoutOverlapping();
        $schedule->command('hub:cron')->everyMinute()->withoutOverlapping();
        $schedule->command('push_order_dms:cron')->everyMinute()->withoutOverlapping();
        $schedule->command('order_export:cron')->cron('0 */6 * * *')->withoutOverlapping();
        $schedule->command('temp_order_export:cron')->cron('*/20 * * * *')->withoutOverlapping();
        $schedule->command('repost_cdp:cron')->cron('*/20 * * * *')->withoutOverlapping();
    }
}
