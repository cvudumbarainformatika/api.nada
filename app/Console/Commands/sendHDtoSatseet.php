<?php

namespace App\Console\Commands;

use App\Helpers\Satsets\PostKunjunganHDHerlper;
use Illuminate\Console\Command;

class sendHDtoSatseet extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'send:hd';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim Kunjunga HD Ke Satu Sehat';

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

        PostKunjunganHDHerlper::cekKunjungan();

        return Command::SUCCESS;
        // return 0;
    }
}
