<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dokter extends Model
{
    use HasFactory;
    protected $connection = 'antrean';
    protected $table = 'dokters';
    protected $guarded = ['id'];

    public function booking()
    {
        return $this->hasOne(Booking::class);
    }

    // public function referensi_poli_bpjs()
    // {
    //     return $this->hasMany(PoliBpjs::class, 'koders', 'kode_simrs');
    // }
}
