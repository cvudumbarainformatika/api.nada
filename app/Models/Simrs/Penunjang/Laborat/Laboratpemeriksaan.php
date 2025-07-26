<?php

namespace App\Models\Simrs\Penunjang\Laborat;

use App\Models\Interpretasi;
use App\Models\Simrs\Master\Mpemeriksaanlab;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laboratpemeriksaan extends Model
{
    use HasFactory;
    protected $table = 'rs51';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function pemeriksaanlab()
    {
        return $this->belongsTo(Mpemeriksaanlab::class, 'rs4', 'rs1');
    }

    public function laboratmeta()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs2', 'nota');
    }

    public function laboratmetas()
    {
        return $this->hasMany(LaboratMeta::class, 'rs2', 'nota');
    }

    public function interpretasi()
    {
        return $this->belongsTo(Interpretasi::class, 'rs5', 'rs2');
    }

    public function getSubtotalAttribute()
    {
        $harga1 = (int) $this->rs6;
        $harga2 = (int) $this->rs13;
        $jumlah = (int) $this->rs5;
        $biaya = $harga1 + $harga2;
        return ($biaya * $jumlah);
    }
}
