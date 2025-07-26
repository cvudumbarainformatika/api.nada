<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Retur\Returpenjualan_h;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokopname;
use App\Models\Simrs\Penunjang\Farmasinew\Stokreal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarrinciracikan extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_racikan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function mobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function stok()
    {
        return $this->hasMany(Stokreal::class, 'kdobat', 'kdobat');
    }
    public function header()
    {
        return $this->belongsTo(Resepkeluarheder::class, 'noresep', 'noresep');
    }
    public function retur()
    {
        return $this->hasMany(Returpenjualan_h::class, 'noresep', 'noresep');
    }
    public function rincipenerimaan()
    {
        return $this->hasMany(PenerimaanRinci::class, 'kdobat', 'kdobat');
    }
    public function opname()
    {
        return $this->hasMany(Stokopname::class, 'kdobat', 'kdobat');
    }


    // dipakai di laporan
    public function dokter()
    {
        return $this->hasone(Petugas::class, 'kdpegsimrs', 'dokter');
    }
}
