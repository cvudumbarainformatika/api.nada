<?php

namespace App\Models\Simrs\Penunjang\Operasi;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanOperasiIrd extends Model
{
    use HasFactory;
    protected $table = 'rs225';
    protected $guarded = ['id'];


    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs', 'rs11');
    }
}
