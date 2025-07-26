<?php

namespace App\Models\Simrs\Penunjang\Bdrs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bdrstrans extends Model
{
    use HasFactory;
    protected $table = 'rs231';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs12;
        $harga2 = $this->rs13;
        $subtotal = $harga1+$harga2;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }
}
