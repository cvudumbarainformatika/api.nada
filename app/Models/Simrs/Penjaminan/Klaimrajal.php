<?php

namespace App\Models\Simrs\Penjaminan;

use App\Models\Simrs\Master\Diagnosa_m;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Klaimrajal extends Model
{
    use HasFactory;
    protected $table = 'klaim_trans_rajal';
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

    // public function diagnosa()
    // {
    //     return $this->belongsTo(Diagnosa_m::class, 'diagnosa', 'rs1');
    // }

    // public function getDiagnosaAttribute()
    // {
    //     $data = explode('#', $this->attributes['diagnosas']);
    //     $val = [];
    //     foreach ($data as $key) {
    //         $temp = Diagnosa_m::where('rs1', $key)->first();
    //         array_push($val, $temp);
    //     }
    //     return $val;
    // }
}
