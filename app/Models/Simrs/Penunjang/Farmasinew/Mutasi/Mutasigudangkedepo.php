<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Mutasi;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mutasigudangkedepo extends Model
{
    use HasFactory;
    protected $table = 'mutasi_gudangdepo';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(Permintaandepoheder::class, 'no_permintaan', 'no_permintaan');
    }
    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    // ini dipake di laporan mutasi ffo
    public function ruangan()
    {
        return $this->belongsTo(Ruang::class, 'kdruang', 'kode');
    }
    // ini dipake di laporan mutasi ffo
    public function depo()
    {
        return $this->belongsTo(Gudang::class, 'kdruang', 'kode');
    }
}
