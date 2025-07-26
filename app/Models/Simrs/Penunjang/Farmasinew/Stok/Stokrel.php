<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Stok;

use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandepoheder;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Mminmaxobat;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Farmasinew\Penerimaan\PenerimaanHeder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokrel extends Model
{
    use HasFactory;
    protected $table = 'stokreal';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function getHargaberlakuAttribute()
    {
        $kdobat = $this->kdobat;
        $daftar = DaftarHarga::selectRaw('max(harga) as harga')
            ->where('kd_obat', $kdobat)
            ->orderBy('tgl_mulai_berlaku', 'desc')
            ->limit(5)
            ->first();
        $harga = $daftar->harga;
        return $harga;
    }
    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kdobat');
    }
    public function harga()
    {
        return $this->hasOne(DaftarHarga::class, 'nopenerimaan', 'nopenerimaan');
    }

    public function permintaanobatrinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'kdobat', 'kdobat');
    }

    public function permintaanobatheder()
    {
        return $this->hasManyThrough(
            Permintaandepoheder::class,
            Permintaandeporinci::class,
            'no_permintaan',
            'no_permintaan'
        );
    }

    public function minmax()
    {
        return $this->hasMany(Mminmaxobat::class, 'kd_obat', 'kdobat');
    }
    public function gudang()
    {
        return $this->belongsTo(Gudang::class, 'kdruang', 'kode');
    }
    public function ruang()
    {
        return $this->belongsTo(Ruang::class, 'kdruang', 'kode');
    }

    public function penerimaan()
    {
        return $this->belongsTo(PenerimaanHeder::class, 'nopenerimaan', 'nopenerimaan');
    }
}
