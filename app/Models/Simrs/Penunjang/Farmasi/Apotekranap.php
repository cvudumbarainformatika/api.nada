<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use App\Models\Simrs\Master\Mobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekranap extends Model
{
    use HasFactory;
    protected $table = 'rs38';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = $this->rs6;
        $harga2 = $this->rs8;
        $harga3 = $this->rs10;
        $subtotal = ($harga1 * $harga2) + $harga3;
        return ($subtotal);
    }

    public function masterobat()
    {
        return $this->hasOne(Mobat::class, 'rs1', 'rs4');
    }
}
