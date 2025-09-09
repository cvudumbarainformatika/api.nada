<?php

namespace App\Models\Simrs\Penunjang\Farmasinew;

use App\Http\Controllers\Api\Simrs\Penunjang\Farmasinew\MinmaxobatController;
use App\Models\Sigarang\Gudang;
use App\Models\Sigarang\Ruang;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaandeporinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinci;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarrinciracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Harga\DaftarHarga;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasiRinci;
use App\Models\Simrs\Penunjang\Farmasinew\Stok\PenyesuaianStok;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stokreal extends Model
{
    use HasFactory;
    protected $table = 'stokreal';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function getHargaMaxAttribute()
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
    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kdobat', 'kd_obat');
    }
    public function minmax()
    {
        return $this->hasOne(Mminmaxobat::class, 'kd_obat', 'kdobat');
    }

    public function gudangdepo()
    {
        return $this->hasOne(Gudang::class, 'kode', 'kdruang');
    }
    public function ruang()
    {
        return $this->hasOne(Ruang::class, 'kode', 'kdruang');
    }

    public function transnonracikan()
    {
        // return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kdobat'); diganti ke permintaan
        return $this->hasMany(Permintaanresep::class, 'kdobat', 'kdobat');
    }

    public function transracikan()
    {
        // return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kdobat');
        return $this->hasMany(Permintaanresepracikan::class, 'kdobat', 'kdobat');
    }
    public function persiapanrinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kdobat');
    }
    public function daftarharga()
    {
        return $this->hasMany(DaftarHarga::class, 'kd_obat', 'kdobat');
    }
    public function permintaanobatrinci()
    {
        return $this->hasMany(Permintaandeporinci::class, 'kdobat', 'kdobat');
    }
    public function ssw()
    {
        return $this->hasMany(PenyesuaianStok::class);
    }
    public function oneobatkel()
    {
        return $this->hasOne(Resepkeluarrinci::class, 'kdobat', 'kdobat');
    }

    public function oneobatkelracikan()
    {
        return $this->hasOne(Resepkeluarrinciracikan::class, 'kdobat', 'kdobat');
    }

    public function onepermintaandeporinci()
    {
        return $this->hasOne(Permintaandeporinci::class, 'kdobat', 'kdobat');
    }

    public function onepermintaan()
    {
        // return $this->hasMany(Resepkeluarrinci::class, 'kdobat', 'kdobat'); //diganti ke permintaan
        return $this->hasOne(Permintaanresep::class, 'kdobat', 'kdobat');
    }

    public function oneperracikan()
    {
        // return $this->hasMany(Resepkeluarrinciracikan::class, 'kdobat', 'kdobat');
        return $this->hasOne(Permintaanresepracikan::class, 'kdobat', 'kdobat');
    }
}
