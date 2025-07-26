<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Retur;

use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturGudangDetail extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function masterobat()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    public function header()
    {
        return $this->belongsTo(ReturGudang::class, 'no_retur', 'no_retur');
    }
}
