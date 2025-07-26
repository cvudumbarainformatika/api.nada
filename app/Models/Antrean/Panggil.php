<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Panggil extends Model
{
    use HasFactory;
    // protected $connection = 'antrean'; // Pindah
    protected $connection = 'newantrean';
    protected $table = 'panggils';
    protected $guarded = ['id'];

    // public function dokter()
    // {
    //     return $this->belongsTo(Dokter::class);
    // }

    // public function referensi_poli_bpjs()
    // {
    //     return $this->hasMany(PoliBpjs::class, 'koders', 'kode_simrs');
    // }
}
