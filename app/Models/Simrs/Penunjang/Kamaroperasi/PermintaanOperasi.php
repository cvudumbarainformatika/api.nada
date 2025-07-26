<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Msistembayar;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi\PersiapanOperasi;
use App\Models\Simrs\Rajal\KunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanOperasi extends Model
{
    use HasFactory;
    protected $table = 'rs200';
    protected $connection = 'mysql';
    protected $guarded = ['id'];

    public function kunjunganranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1', 'rs1');
    }


    public function kunjunganrajal()
    {
        return $this->hasOne(KunjunganPoli::class, 'rs1', 'rs1');
    }

    public function sistembayar()
    {
        return $this->hasOne(Msistembayar::class, 'rs1', 'rs14');
    }

    public function dokter()
    {
        return $this->hasOne(Pegawai::class, 'kdpegsimrs', 'rs8');
    }
    public function permintaanobatoperasi()
    {
        return $this->hasMany(PersiapanOperasi::class, 'noreg', 'rs1');
    }
    public function newapotekrajal()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
}
