<?php

namespace App\Models\Simrs\Penunjang\Kamarjenazah;

use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamarjenasahtrans extends Model
{
    use HasFactory;

    protected $table = 'rs273';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs6;
        $harga2 = $this->rs7;
        $subtotal = $harga1+$harga2;
       // $data->select($subtotal)->where('rs3','=','RM#')->get();
        return ($subtotal);
    }

    public function pelayananjenazah()
    {
        return $this->hasOne(Mtindakan::class, 'rs1', 'rs5');
    }
}
