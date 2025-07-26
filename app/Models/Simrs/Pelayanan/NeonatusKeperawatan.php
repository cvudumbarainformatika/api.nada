<?php

namespace App\Models\Simrs\Pelayanan;

use App\Models\Sigarang\Pegawai;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeonatusKeperawatan extends Model
{
    use HasFactory;
    protected $table = 'neonatuskeperawatan';
    protected $guarded = ['id'];

    protected $casts = [
      'kebiasaanIbu' => 'array',
      'refleks' => 'array',
      'tangisBayi' => 'array',
      'lakilaki' => 'array',
      'perempuan' => 'array',
      'makananPokok' => 'array',
    ];

    public function pegawai()
    {
       return $this->belongsTo(Pegawai::class,'user_input', 'id');
    }
}
