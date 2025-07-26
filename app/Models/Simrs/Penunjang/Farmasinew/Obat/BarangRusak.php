<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obat;

use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangRusak extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];

    public function pihakketiga()
    {
        return $this->hasOne(Mpihakketiga::class, 'kode', 'kdpbf');
    }
    public function masterobat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
}
