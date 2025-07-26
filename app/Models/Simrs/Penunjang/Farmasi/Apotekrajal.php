<?php

namespace App\Models\Simrs\Penunjang\Farmasi;

use App\Models\Simrs\Master\Mobat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apotekrajal extends Model
{
    use HasFactory;
    protected $table = 'rs90';
    protected $guarded = [''];
    public $timestamps = false;
    protected $primaryKey = 'rs1';
    protected $keyType = 'string';
    protected $appends = ['subtotal'];

    public function masterobat()
    {
        return $this->hasOne(Mobat::class, 'rs1', 'rs4');
    }

    public function getSubtotalAttribute()
    {
        $harga = $this->rs6;
        $jumlah = $this->rs8;
        $jumlah_r = $this->rs10;
        return (($harga * $jumlah) + $jumlah_r);
    }
}
