<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersiapanOperasiRinci extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function header()
    {
        return $this->belongsTo(PersiapanOperasi::class, 'nopermintaan', 'nopermintaan');
    }
    public function resep()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noresep', 'noresep');
    }
    public function penerimaanrinci()
    {
        return $this->hasMany(PenerimaanRinci::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function rincian()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'noresep', 'noresep');
    }

    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanHeder::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function susulan()
    {
        return $this->belongsTo(Pegawai::class, 'susulan', 'kdpegsimrs');
    }
}
