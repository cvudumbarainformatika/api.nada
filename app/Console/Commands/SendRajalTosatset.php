<?php

namespace App\Console\Commands;

use App\Helpers\Satsets\PostKunjunganRajalHelper;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendRajalTosatset extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:rajal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim Kunjungan Rajal Ke Satu Sehat';

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
     * @return int
     */
    public function handle()
    {
        // $this->info('Kirim Kunjungan Rajal Ke Satu Sehat ' .Carbon::now()->toDateTimeLocalString());

        PostKunjunganRajalHelper::cekKunjungan();

        return Command::SUCCESS;
        // return 0;
    }
}
