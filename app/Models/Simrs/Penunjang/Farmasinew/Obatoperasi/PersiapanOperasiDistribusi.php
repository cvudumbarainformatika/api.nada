<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Obatoperasi;

use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Master\Mpihakketiga;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PersiapanOperasiDistribusi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function rinci()
    {
        return $this->hasMany(PersiapanOperasiRinci::class, 'kd_obat', 'kd_obat');
    }

    public function persiapan()
    {
        return $this->belongsTo(PersiapanOperasi::class, 'nopermintaan', 'nopermintaan');
    }
    public function master()
    {
        return $this->belongsTo(Mobatnew::class, 'kd_obat', 'kd_obat');
    }
    public function pbf()
    {
        // ini dipake jika di join dengan penerimaan_r dan penerimaan_h, dan penerimaan_h.kdpbf di select.
        return $this->belongsTo(Mpihakketiga::class, 'kdpbf', 'kode');
    }
    public function pasien()
    {
        // ini dipake jika di join dengan header nya( persiapan_operasis) dan norm di select.
        return $this->hasOne(Mpasien::class, 'rs1', 'norm');
    }
}
