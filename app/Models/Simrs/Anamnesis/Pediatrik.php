<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pediatrik extends Model
{
    use HasFactory;
    protected $table = 'rs209_pediatrik';
    protected $guarded = ['id'];
    protected $casts = [
      'rwImunisasi' => 'array',
    ];


    public function petugas()
    {
        return  $this->hasOne(Petugas::class, 'kdpegsimrs', 'user');
    }
}
