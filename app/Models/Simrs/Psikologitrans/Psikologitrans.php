<?php

namespace App\Models\Simrs\Psikologitrans;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Psikologitrans extends Model
{
    use HasFactory;
    protected $table = 'psikologi_trans';
    protected $guarded = [''];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $harga1 = $this->rs7;
        $harga2 = $this->rs13;
        $jumlah = $this->rs5;
        $subtotal = ($harga1+$harga2)*$jumlah;
        return ($subtotal);
    }
}
