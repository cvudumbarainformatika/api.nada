<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkreeningGizi extends Model
{
    use HasFactory;
    protected $table = 'rs209_gizi';
    protected $guarded = ['id'];
    protected $casts = [
      'dewasa' => 'array',
      'kebidanan' => 'array',
      'neonatal' => 'array',
      'pediatrik'=> 'array'
    ];


    public function petugas()
    {
        return  $this->hasOne(Petugas::class, 'kdpegsimrs', 'user');
    }
}
