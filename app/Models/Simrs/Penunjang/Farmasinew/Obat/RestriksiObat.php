<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obat;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestriksiObat extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $guarded = ['id'];

    public function obat()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
}
