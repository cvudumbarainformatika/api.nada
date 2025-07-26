<?php

namespace App\Models\Simrs\Kasir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'rs35';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs7;
        $harga2 = $this->rs11;
        $subtotal = $harga1 + (int)$harga2;
        // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }
}
