<?php

namespace App\Models\Simrs\Tindakan;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Ews\MapingProcedure;
use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Master\Mtindakan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TindakanSambung extends Model
{
    use HasFactory;
    protected $table = 'rs73_sambung';
    protected $guarded = ['id'];

    // public function getSubtotalAttribute()
    // {
    //     $harga1 = (int) $this->rs7 ? $this->rs7 : 0;
    //     $harga2 = (int)  $this->rs13 ? $this->rs13 : 0;
    //     $jumlah = (int) $this->rs5 ? $this->rs5 : 1;

    //     $hargatotal = $harga1 + $harga2;
    //     $subtotal = $hargatotal * $jumlah;
    //     //$subtotal = ($harga1+$harga2)*$jumlah;
    //     return ($subtotal);
    // }

    
}
