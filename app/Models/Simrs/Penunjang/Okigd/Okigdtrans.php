<?php

namespace App\Models\Simrs\Penunjang\Okigd;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Okigdtrans extends Model
{
    use HasFactory;
    protected $table = 'rs226';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs5;
        $harga2 = $this->rs6;
        $harga3 = $this->rs7;
        $subtotal = $harga1+$harga2+$harga3;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }
}
