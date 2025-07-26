<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;

use App\Models\Simrs\Master\Mpoli;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Display extends Model
{
    use HasFactory;
    // protected $connection = 'antrean'; // Pindah
    protected $connection = 'newantrean';
    protected $table = 'displays';
    protected $guarded = ['id'];

    public function unit()
    {
        return $this->hasMany(Unit::class, 'display_id', 'kode');
    }
    public function poli()
    {
        return $this->hasMany(Mpoli::class, 'displaykode', 'kode');
    }

    // public function referensi_poli_bpjs()
    // {
    //     return $this->hasMany(PoliBpjs::class, 'koders', 'kode_simrs');
    // }
}
