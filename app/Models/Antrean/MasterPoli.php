<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterPoli extends Model
{
    use HasFactory;
    protected $connection = 'antrean';
    protected $table = 'masterpoli';
    protected $guarded = ['id'];

    public function poli_bpjs()
    {
        return $this->hasOne(PoliBpjs::class, 'kode', 'kode_bpjs');
    }

    public function referensi_poli_bpjs()
    {
        return $this->hasMany(PoliBpjs::class, 'koders', 'kode_simrs');
    }
}
