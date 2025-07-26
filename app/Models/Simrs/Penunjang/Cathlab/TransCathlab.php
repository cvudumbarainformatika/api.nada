<?php

namespace App\Models\Simrs\Penunjang\Cathlab;

use App\Models\Pegawai\Mpegawaisimpeg;
use App\Models\Simrs\Master\Mtarifcathlab;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransCathlab extends Model
{
    use HasFactory;
    protected $table = 'cathlab';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];

    public function tarif()
    {
        return $this->hasOne(Mtarifcathlab::class, 'kode', 'kd_tindakan');
    }

    public function getSubtotalAttribute()
    {
        $js = (int) $this->js ? $this->js : 0;
        $jp = (int)  $this->jp ? $this->jp : 0;

        $hargatotal = $js + $jp;
        //$subtotal = ($harga1+$harga2)*$jumlah;
        return ($hargatotal);
    }

    public function pelaksana1()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'pelaksana1');
    }

    public function pelaksana2()
    {
        return  $this->hasOne(Mpegawaisimpeg::class, 'kdpegsimrs', 'pelaksana2');
    }
}
