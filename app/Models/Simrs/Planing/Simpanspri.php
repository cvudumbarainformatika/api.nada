<?php

namespace App\Models\Simrs\Planing;

use App\Models\Simpeg\Petugas;
use App\Models\Simrs\Penunjang\Kamaroperasi\JadwaloperasiController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simpanspri extends Model
{
    use HasFactory;
    protected $table = 'bpjs_spri';
    protected $guarded = ['id'];

    public function jadwaloperasi()
    {
        return $this->hasOne(JadwaloperasiController::class, 'noreg', 'noreg');
    }

    public function petugas()
    {
       return $this->hasOne(Petugas::class, 'kdpegsimrs', 'kodeDokter');
    }
}
