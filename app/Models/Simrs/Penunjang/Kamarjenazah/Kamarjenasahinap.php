<?php

namespace App\Models\Simrs\Penunjang\Kamarjenazah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamarjenasahinap extends Model
{
    use HasFactory;
    protected $table = 'rs275';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs5;
        $harga2 = $this->rs6;
        $subtotal = $harga1+$harga2;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }
}
