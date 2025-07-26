<?php

namespace App\Models\Simrs\Penunjang\Gizi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AsuhanGizi extends Model
{
    use HasFactory;
    protected $table = 'rs202';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs4;
        $harga2 = $this->rs5;
        $subtotal = $harga1+$harga2;
        return ($subtotal);
    }
}
