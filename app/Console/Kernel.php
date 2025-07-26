<?php

namespace App\Console;

use App\Http\Controllers\Api\Logistik\Sigarang\Transaksi\StokOpnameController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController;
use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\StokOpnameFarmasiController;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */

    protected $commands = [
        \App\Console\Commands\MergeSwooleToDeploy::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:rajal')
            ->everyMinute()
            ->between('16:30', '23:50');

        $schedule->command('send:hd')
            ->everyMinute()
            ->between('17:30', '23:50');


        $schedule->command('cache:clear')
            ->dailyAt('00:30');
        // $schedule->call(function () {
        //     Artisan::call('cache:clear'); // you can move this part to Job
        // })
        // ->dailyAt('01:00');




        // apakah perlu ditambahkan cek stok setiap hari?????????

        $schedule->call(function () {
            info('mulai stok opname farmasi');
            $opname = new StokOpnameFarmasiController;
            $data = $opname->storeMonthly();
            info($data);
        })->dailyAt('00:20');
        // $schedule->command('inspire')->hourly();
        $schedule->call(function () {
            info('mulai stok opname');
            $opname = new StokOpnameController;
            $data = $opname->storeMonthly();
            info($data);
        })->dailyAt('00:30');

        // perbaikan data per depo start

        $schedule->command('stok:gko')
            ->dailyAt('22:00');
        $schedule->command('stok:gfo')
            ->dailyAt('22:10');
        $schedule->command('stok:dfo')
            ->dailyAt('22:20');
        $schedule->command('stok:drj')
            ->dailyAt('22:30');
        $schedule->command('stok:dri')
            ->dailyAt('23:20');
        $schedule->command('stok:dok')
            ->dailyAt('22:50');
        $schedule->command('stok:dir')
            ->dailyAt('23:00');


        // perbaikan data per depo end


        // $schedule->call(function () {
        //     info('mulai');
        //     $opname = new StokOpnameController;
        //     $data = $opname->storeCoba();
        //     info($data);
        // })->hourlyAt(16);
        // $schedule->call(function () {

        //     info('nyoba');
        // })->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
