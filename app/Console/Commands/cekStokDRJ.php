<?php

namespace App\Console\Commands;

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\Stok\SetNewStokController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class cekStokDRJ extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stok:drj';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'cek Stok Depo Rawat Jalan';

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
        $depo = new Request([
            'kdruang' => 'Gd-05010101'
        ]);
        info('perbaikan data per depo ' . $depo);
        $controller = new SetNewStokController;
        $data = $controller->PerbaikanStokPerDepo($depo);
        info($data);

        return Command::SUCCESS;
        // return 0;
    }
}
