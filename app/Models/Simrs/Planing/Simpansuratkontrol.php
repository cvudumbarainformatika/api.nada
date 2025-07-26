<?php

namespace App\Models\Simrs\Planing;

use App\Models\Sigarang\Ruang;
use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simpansuratkontrol extends Model
{
    use HasFactory;
    protected $table = 'bpjs_surat_kontrol';
    protected $guarded = ['id'];

    public function dokterkontrol()
    {
        return $this->hasOne(Petugas::class, 'kddpjp', 'kdDokterKontrol');
    }

    public function lokasikontrol()
    {
       return $this->hasOne(Ruang::class, 'kode', 'kode_ruang');
    }
}
