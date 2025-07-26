<?php

namespace App\Models\Simrs\Penjaminan;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klaimranap extends Model
{
    use HasFactory;
    protected $table = 'klaim_trans_ranap';
    protected $guarded = ['id'];
    public $timestamps = false;
    protected $appends = ['subtotal'];

    public function getSubtotalAttribute()
    {
        $konsultasi = $this->konsultasi;
        $tenaga_ahli = $this->tenaga_ahli;
        $keperawatan = $this->keperawatan;
        $penunjang = $this->penunjang;
        $radiologi = $this->radiologi;
        $darah = $this->Pelayanan_darah;
        $rehabilitasi = $this->rehabilitasi;
        $kamar = $this->kamar;
        $rawat_intensif = $this->rawat_intensif;
        $obat = $this->obat;
        $alkes = $this->alkes;
        $bmhp = $this->bmhp;
        $sewa_alat = $this->sewa_alat;
        $tarif_poli_eks = $this->tarif_poli_eks;
        $subtotal = ($konsultasi + $tenaga_ahli + $keperawatan + $penunjang + $radiologi + $darah + $rehabilitasi + $kamar + $rawat_intensif + $obat + $alkes + $bmhp + $sewa_alat + $tarif_poli_eks);
        return ($subtotal);
    }
}
