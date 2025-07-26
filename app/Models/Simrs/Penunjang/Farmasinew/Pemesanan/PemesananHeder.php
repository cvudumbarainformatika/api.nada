<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Pemesanan;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanRinci;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PemesananHeder extends Model
{
    use HasFactory;
    protected $table = 'pemesanan_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function pihakketiga()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }

    public function rinci()
    {
        return $this->hasMany(PemesananRinci::class, 'nopemesanan', 'nopemesanan');
    }

    public function penerimaan()
    {
        // return $this->hasManyThrough(
        //     PenerimaanHeder::class,
        //     PenerimaanRinci::class,
        //     'nopenerimaan',
        //     'nopenerimaan',
        //     'nopemesanan',
        //     'nopenerimaan',
        // );
        return $this->hasMany(PenerimaanHeder::class, 'nopemesanan', 'nopemesanan');
    }

    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'kd_ruang', 'kode');
    }
}
