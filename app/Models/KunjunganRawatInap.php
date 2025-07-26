<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganRawatInap extends Model
{
    use HasFactory;
    protected $table = 'rs23';

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'rs2', 'rs1');
    }
    public function ruangan()
    {
        return $this->belongsTo(RuanganRawatInap::class, 'rs5', 'rs1');
    }

    public function transaksi_laborat()
    {
        return $this->hasMany(TransaksiLaborat::class, 'rs1', 'rs1');
    }
    public function sistem_bayar()
    {
        return $this->belongsTo(SistemBayar::class, 'rs19', 'rs1');
    }
}
