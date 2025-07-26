<?php

namespace App\Models\Simrs\Ranap\Pelayanan\Pemeriksaan;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penilaian extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'penilaian';
    protected $guarded = ['id'];
    protected $casts = [
        'barthel' => 'array',
        'norton' => 'array',
        'humpty_dumpty' => 'array',
        'morse_fall' => 'array',
        'ontario' => 'array',
      ];

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs','user');
    }
    

    
}
