<?php

namespace App\Models\Simrs\Konsultasi;

use App\Models\KunjunganPoli;
use App\Models\Sigarang\Pegawai;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Master\Diagnosa_m;
use App\Models\Simrs\Rajal\KunjunganPoli as RajalKunjunganPoli;
use App\Models\Simrs\Ranap\Kunjunganranap;
use App\Models\Simrs\Tindakan\Tindakan;
use App\Models\Simrs\Visite\Visite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Konsultasi extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'konsultasi_rs140';
    protected $guarded = ['id'];

    public function tarif()
    {
        return $this->hasOne(Visite::class, 'id', 'rs140_id');
    }

    public function kunjunganranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1', 'noreg');
    }
    public function kunjunganpoli()
    {
        return $this->hasOne(RajalKunjunganPoli::class, 'rs1', 'noreg');
    }
    public function kunjunganigd()
    {
        return $this->hasOne(RajalKunjunganPoli::class, 'rs1', 'noreg');
    }

    public function dokterkonsul()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kddokterkonsul');
    }

    public function nakesminta()
    {
        return $this->hasOne(Petugas::class,  'kdpegsimrs', 'kdminta');
    }
    public function userinput()
    {
        return $this->hasOne(Petugas::class, 'kdpegsimrs', 'user' );
    }

    public function tindakan()
    {
        return $this->hasMany(Tindakan::class, 'id', 'rs140_id' );
    }




}
