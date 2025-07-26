<?php

namespace App\Models\Simrs\Penunjang\PenunjangKeluar;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenunjangKeluar extends Model
{
    use HasFactory;
    protected $table = 'lab_keluar';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->harga_sarana;
        $harga2 = $this->harga_pelayanan;
        $harga3 = $this->jumlah;
        $subtotal = ($harga1+$harga2)*$harga3;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }
}
