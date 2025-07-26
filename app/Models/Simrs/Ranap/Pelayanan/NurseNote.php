<?php

namespace App\Models\Simrs\Ranap\Pelayanan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Anamnesis\Anamnesis;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\PemeriksaanUmum;
use App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan\Penilaian;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseNote extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'nurse_notes';
    protected $guarded = ['id'];
    protected $casts = [
        'tindakan' => 'array',
        'reseps' => 'array',
        'flag' => 'array',
      ];

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs','user');
    }
    
    

    
}
