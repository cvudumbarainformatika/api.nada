<?php

namespace App\Models\Simrs\Kasir;

use App\Models\KunjunganRawatInap;
use App\Models\Simrs\Master\Rstigapuluhtarif;
use App\Models\Simrs\Ranap\Kunjunganranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rstigalimax extends Model
{
    use HasFactory;
    protected $table = 'rs35x';
    protected $guarded = ['id'];
    protected $appends = ['subtotal'];
    public $timestamps = false;

    public function rstigapuluhtarif()
    {
        return $this->hasMany(Rstigapuluhtarif::class, 'rs1','rs1');
    }

    public function kunjunganranap()
    {
        return $this->hasOne(Kunjunganranap::class, 'rs1','rs1');
    }

    public function getSubtotalAttribute($data)
    {
        $harga1 = $this->rs7;
        $harga2 = $this->rs14;
        $subtotal = $harga1+$harga2;
        return ($subtotal);
    }

}
