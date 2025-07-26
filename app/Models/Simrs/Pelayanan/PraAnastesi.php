<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Simrs\Pelayanan\Diagnosa\Diagnosa;
use App\Models\Simrs\Pemeriksaanfisik\Pemeriksaanfisik;
use App\Models\Simrs\Rajal\KunjunganPoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PraAnastesi extends Model
{
    use HasFactory;
    protected $table = 'pra_anastesi';
    protected $guarded = ['id'];
    protected $casts = [
      'asaClasification' => 'array',
      'kajianSistem' => 'array',
      'laboratorium' => 'array',
      'penyulitAnastesi' => 'array',
      'teknikAnestesia' => 'array',
      'teknikKhusus' => 'array',
      'pascaAnastesi' => 'array',
  ];

    public function kunjunganpoli()
    {
      return $this->hasOne(KunjunganPoli::class, 'rs1', 'noreg');
    }
    public function pemeriksaanfisik()
    {
      return $this->hasMany(Pemeriksaanfisik::class, 'rs1', 'noreg');
    }
    public function diagnosa()
    {
      return $this->hasMany(Diagnosa::class, 'rs1', 'rs1');
    }
}
