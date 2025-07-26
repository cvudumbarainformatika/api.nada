<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Master\Mobat;
use App\Models\Simrs\Master\Mruangan;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mminmaxobat extends Model
{
    use HasFactory;
    protected $table = 'min_max_ruang';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    // public function obat()
    // {
    //     return $this->belongsTo(Mobat::class, 'kd_obat', 'rs1');
    // }

    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }

    public function ruanganx()
    {
        return $this->belongsTo(Mruangan::class, 'kd_ruang', 'kode');
    }
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'kd_ruang', 'kode');
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kd_ruang', 'kode');
    }
    public function perencanaanrinci()
    {
        return $this->hasMany(RencanabeliR::class, 'kdobat', 'kd_obat');
    }

    public function stokreal()
    {
        return $this->hasMany(stokreal::class, 'kdobat', 'kd_obat');
    }
}
