<?php

namespace App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaan_Psikologoldll;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanUmum extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'rs253';
    protected $guarded = ['id'];
    protected $casts = [
        // 'riwayatalergi' => 'array',
      ];


    public function datasimpeg()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'user');
    }

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs','user');
    }
    public function kebidanan()
    {
       return $this->hasOne(PemeriksaanKebidanan::class, 'rs253_id','id');
    }
    public function neonatal()
    {
       return $this->hasOne(PemeriksaanNeonatal::class, 'rs253_id','id');
    }
    public function pediatrik()
    {
       return $this->hasOne(PemeriksaanPediatrik::class, 'rs253_id','id');
    }
    public function penilaian()
    {
       return $this->hasOne(Penilaian::class, 'rs1','rs1');
    }
    public function pemerisaanpsikologidll()
    {
        return  $this->hasOne(Pemeriksaan_Psikologoldll::class, 'id_rs253', 'id');
    }




}
