<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Depo;

use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Kasir\Kwitansilog;
use App\Models\Simrs\Master\MkamarRanap;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pendaftaran\Rajalumum\Antrianambil;
use App\Models\Simrs\Pendaftaran\Rajalumum\Seprajal;
use App\Models\Simrs\Penunjang\Farmasinew\PelayananInformasiObat;
use App\Models\Simrs\Penunjang\Farmasinew\TelaahResep;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Ranap\Mruangranap;
use App\Models\SistemBayar;
use App\Models\TransaksiLaborat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resepkeluarheder extends Model
{
    use HasFactory;
    protected $table = 'resep_keluar_h';
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rincian()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'noresep', 'noresep');
    }

    public function rincianwret()
    {
        return $this->hasMany(Resepkeluarrinci::class, 'noresep', 'noresep');
    }
    public function rincianracik()
    {
        return $this->hasMany(Resepkeluarrinciracikan::class, 'noresep', 'noresep');
    }
    public function rincianracikwret()
    {
        return $this->hasMany(Resepkeluarrinciracikan::class, 'noresep', 'noresep');
    }

    public function dokter()
    {
        return $this->hasone(Petugas::class, 'kdpegsimrs', 'dokter');
    }
    public function ketdokter()
    {
        return $this->hasone(Petugas::class, 'kdpegsimrs', 'dokter');
    }

    public function sistembayar()
    {
        return $this->hasone(SistemBayar::class, 'rs1', 'sistembayar');
    }

    public function datapasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }

    public function asalpermintaanresep()
    {
        return $this->hasMany(Permintaanresep::class, 'noresep', 'noresep_asal');
    }
    public function permintaanresep()
    {
        return $this->hasMany(Permintaanresep::class, 'noresep', 'noresep');
    }
    public function asalpermintaanracikan()
    {
        return $this->hasMany(Permintaanresepracikan::class, 'noresep', 'noresep_asal');
    }
    public function permintaanracikan()
    {
        return $this->hasMany(Permintaanresepracikan::class, 'noresep', 'noresep');
    }
    public function poli()
    {
        return $this->belongsTo(Mpoli::class, 'ruangan', 'rs1');
    }

    public function ruanganranap()
    {
        return $this->belongsTo(Mruangranap::class, 'ruangan', 'rs1');
    }
    public function info()
    {
        return $this->belongsTo(PelayananInformasiObat::class, 'noreg', 'noreg');
    }
    public function sep()
    {
        return $this->belongsTo(Seprajal::class, 'noreg', 'rs1');
    }
    public function antrian()
    {
        return $this->hasOne(Antrianambil::class, 'noreg', 'noreg');
    }
    public function kunjunganranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1', 'noreg');
    }
    public function kwitansi()
    {
        return $this->hasMany(Kwitansilog::class, 'noreg', 'noreg');
    }
    public function diagnosas()
    {
        return $this->hasMany(Diagnosa::class, 'rs1', 'noreg');
    }
    public function laborat()
    {
        return $this->hasMany(TransaksiLaborat::class, 'rs1', 'noreg');
    }
    public function kunjunganrajal()
    {
        return $this->hasMany(KunjunganPoli::class, 'rs1', 'noreg');
    }

    public function petugas()
    {
        return $this->hasOne(Petugas::class, 'id', 'user');
    }

    public function telaah()
    {
        return $this->hasOne(TelaahResep::class, 'noresep', 'noresep');
    }
}
