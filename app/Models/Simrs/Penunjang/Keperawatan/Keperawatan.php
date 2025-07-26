<?php

namespace App\Models\Simrs\Penunjang\Keperawatan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keperawatan extends Model
{
    use HasFactory;

    protected $table = 'rs203';
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
