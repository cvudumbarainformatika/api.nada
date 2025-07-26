<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Obat\BarangRusak;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\Stokrel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanRinci extends Model
{
    use HasFactory;
    protected $table = 'penerimaan_r';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }

    public function header()
    {
        return $this->belongsTo(PenerimaanHeder::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function pengembalian_rinci()
    {
        return $this->hasMany(PengembalianRinci::class, 'id_rincipenerimaan', 'id_rincipenerimaan');
    }
    public function pbf()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }
    public function stokterima()
    {
        return $this->hasMany(Stokrel::class, 'nopenerimaan', 'nopenerimaan');
    }
    public function stokadalwarsa()
    {
        return $this->hasMany(Stokrel::class, 'nopenerimaan', 'nopenerimaan');
    }
    // public function belumkembali()
    // {
    //     return $this->hasMany(BarangRusak::class, 'nopenerimaan', 'nopenerimaan');
    // }
    // public function sudahkembali()
    // {
    //     return $this->hasMany(BarangRusak::class, 'nopenerimaan', 'nopenerimaan');
    // }
}
