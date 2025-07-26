<?php

namespace App\Models;

use App\Models\Simrs\Rekom\Rekomdpjp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KunjunganPoli extends Model
{
    use HasFactory;

    protected $table = 'rs17';

    public function transaksi_laborat()
    {
        return $this->hasOne(TransaksiLaborat::class, 'rs1', 'rs1');
    }
    public function sistem_bayar()
    {
        return $this->belongsTo(SistemBayar::class, 'rs14', 'rs1');
    }

    public function pasien()
    {
        return $this->belongsTo(Pasien::class, 'rs2', 'rs1');
    }

    public function poli()
    {
        return $this->belongsTo(Poli::class, 'rs8', 'rs1');
    }

    public function status_poli()
    {
        return $this->hasOne(StatusPasienPoli::class, 'rs1', 'rs1');
    }


}
