<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Simrs\Laporan\Operasi\LaporanOperasi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kamaroperasi extends Model
{
    use HasFactory;
    protected $table = 'rs54';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $keyType = 'string';
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = (int) $this->rs5;
        $harga2 = (int) $this->rs6;
        $harga3 = (int) $this->rs7;
        $jumlah = (int) $this->rs8;
        $biaya = ($harga1 + $harga2 + $harga3) * $jumlah;
        return ($biaya);
    }

    public function mastertindakanoperasi()
    {
        return $this->hasOne(Masteroperasi::class, 'rs1', 'rs4');
    }
    public function laporanoperasi()
    {
        return $this->hasOne(LaporanOperasi::class, 'tindakan', 'id');
    }
}
