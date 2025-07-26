<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obat;

use App\Models\RuanganRawatInap;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestriksiObatKecualiRuangan extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];

    public function ruang()
    {
        return $this->belongsTo(Mruangranap::class, 'kd_ruang', 'rs1');
    }
}
