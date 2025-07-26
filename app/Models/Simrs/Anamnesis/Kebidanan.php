<?php

namespace App\Models\Simrs\Anamnesis;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kebidanan extends Model
{
    use HasFactory;
    protected $table = 'rs209_kebidanan';
    protected $guarded = ['id'];
    protected $casts = [
      'rwGynecology' => 'array',
      'kondisiMens' => 'array',
      'keluhans' => 'array',
      'keluhanBak' => 'array',
    ];


    public function petugas()
    {
        return  $this->hasOne(Petugas::class, 'kdpegsimrs', 'user');
    }
}
