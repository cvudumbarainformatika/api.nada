<?php

namespace App\Models\Simrs\Penunjang\Bankdarah;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PermintaanBankdarah extends Model
{
    use HasFactory;
    protected $table = 'rs216';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 =(int) $this->rs12 ?? 0;
        $harga2 =(int) $this->rs13 ?? 0;
        $subtotal = $harga1+$harga2;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }
}
