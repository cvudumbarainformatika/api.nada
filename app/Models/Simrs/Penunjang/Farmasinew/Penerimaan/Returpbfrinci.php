<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Penerimaan;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Returpbfrinci extends Model
{
    use HasFactory;
    protected $connection = 'farmasi';
    protected $table = 'retur_penyedia_r';
    protected $guarded = ['id'];

    public function mobatnew()
    {
        return $this->hasOne(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    public function header()
    {
        return $this->belongsTo(Returpbfheder::class, 'no_retur', 'no_retur');
    }
}
