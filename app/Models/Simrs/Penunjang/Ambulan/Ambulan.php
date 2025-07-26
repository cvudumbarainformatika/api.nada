<?php

namespace App\Models\Simrs\Penunjang\Ambulan;

use App\Models\Simrs\Master\Mnakes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ambulan extends Model
{
    use HasFactory;
    protected $table = 'rs283';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs2;
        $harga2 = $this->rs15;
        $harga3 = $this->rs16;
        $harga4 = $this->rs17;
        $harga5 = $this->rs18;
        $harga6 = $this->rs23;
        $harga7 = $this->rs26;
        $harga8 = $this->rs30;
        $subtotal = $harga1+$harga2+$harga3+$harga4+$harga5+$harga6+$harga7+$harga8;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }

    public function tujuan()
    {
        return $this->hasOne(TujuanAmbulan::class, 'rs1','rs10');
    }

    public function perawat()
    {
        return $this->hasOne(Mnakes::class, 'rs1','rs13');
    }
    public function perawat2()
    {
        return $this->hasOne(Mnakes::class, 'rs1','rs14');
    }
}
