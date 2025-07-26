<?php

namespace App\Models\Antrean;

// use App\Models\Executive\KeuTransPendapatan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;
    protected $connection = 'antrean';
    protected $table = 'units';
    protected $guarded = ['id'];

    public function layanan()
    {
        return $this->belongsTo(Layanan::class, 'layanan_id', 'id_layanan');
    }
    public function display()
    {
        return $this->belongsTo(Display::class, 'display_id', 'kode');
    }

    // public function referensi_poli_bpjs()
    // {
    //     return $this->hasMany(PoliBpjs::class, 'koders', 'kode_simrs');
    // }
}
