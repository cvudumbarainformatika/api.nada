<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use App\Models\Simrs\Penunjang\Kamaroperasi\PermintaanOperasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersiapanOperasi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function resep()
    {
        return $this->hasOne(Resepkeluarheder::class, 'noreg', 'noreg');
    }
    public function list()
    {
        return $this->hasOne(PermintaanOperasi::class, 'rs1', 'noreg');
    }
    public function rinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'nopermintaan', 'nopermintaan');
    }
    public function distribusi()
    {
        return $this->hasMany(PersiapanOperasiDistribusi::class, 'nopermintaan', 'nopermintaan');
    }
    public function pasien()
    {
        return $this->belongsTo(Mpasien::class, 'norm', 'rs1');
    }

    public function userminta()
    {
        return $this->belongsTo(Pegawai::class, 'user_minta', 'kdpegsimrs');
    }
    public function userdist()
    {
        return $this->belongsTo(Pegawai::class, 'user_distribusi', 'kdpegsimrs');
    }
    public function dokter()
    {
        return $this->belongsTo(Pegawai::class, 'dokter', 'kdpegsimrs');
    }
}
