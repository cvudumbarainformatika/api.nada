<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Template;

use App\Models\Simpeg\Petugas;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KamarOperasiTemplate extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(KamarOperasiDetailTemplate::class, 'kamar_operasi_template_id');
    }
    public function pegawai()
    {
        return $this->belongsTo(Petugas::class, 'pegawai_id');
    }
}
