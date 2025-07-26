<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use App\Models\Simrs\Master\Mobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekranapracikanrinci extends Model
{
    use HasFactory;
    protected $table = 'rs40';
    protected $guarded = ['id'];
    public $timestamps = false;

    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = $this->rs5;
        $harga2 = $this->rs7;
        $subtotal = ($harga1 * $harga2);
        return ($subtotal);
    }

    public function masterobat()
    {
        return $this->hasOne(Mobat::class, 'rs1', 'rs4');
    }
}
