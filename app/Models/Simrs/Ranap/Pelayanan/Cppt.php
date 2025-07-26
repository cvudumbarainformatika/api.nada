<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cppt extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'cppts';
    protected $guarded = ['id'];
    // protected $casts = [
    //     'barthel' => 'array',
    //     'norton' => 'array',
    //     'humpty_dumpty' => 'array',
    //     'morse_fall' => 'array',
    //     'ontario' => 'array',
    //   ];

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs','user');
    }
    
    public function anamnesis()
    {
       return $this->hasOne(Anamnesis::class, 'id','rs209_id');
    }
    public function pemeriksaan()
    {
       return $this->hasOne(PemeriksaanUmum::class, 'id','rs253_id');
    }

    public function penilaian()
    {
       return $this->hasOne(Penilaian::class, 'id','penilaian_id');
    }
    public function cpptlama()
    {
       return $this->hasOne(PemeriksaanUmum::class, 'id','rs213_id');
    }

    
}
