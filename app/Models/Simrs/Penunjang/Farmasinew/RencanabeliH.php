<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Sigarang\Gudang;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Pemesanan\PemesananRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RencanabeliH extends Model
{
    use HasFactory;
    protected $table = 'perencana_pebelian_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rincian()
    {
        return $this->hasMany(RencanabeliR::class, 'no_rencbeliobat', 'no_rencbeliobat');
    }

    public function pihakketiga()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kodepbf');
    }
    public function gudang()
    {
        return $this->hasOne(Gudang::class, 'kode', 'kd_ruang');
    }

    public function pemesananrinci()
    {
        return $this->hasMany(PenerimaanRinci::class, 'noperencanaan', 'no_rencbeliobat');
    }
}
