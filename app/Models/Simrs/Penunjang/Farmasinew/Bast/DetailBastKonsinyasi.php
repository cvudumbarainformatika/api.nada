<?php

namespace App\Models\Simrs\Penunjang\Farmasinew\Bast;

use App\Models\Sigarang\Pegawai;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Penunjang\Farmasinew\Mobatnew;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailBastKonsinyasi extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected $connection = 'farmasi';

    public function obat()
    {
        return $this->belongsTo(Mobatnew::class, 'kdobat', 'kd_obat');
    }
    public function iddokter()
    {
        return $this->belongsTo(Pegawai::class, 'dokter', 'kdpegsimrs');
    }
    public function pasien()
    {
        return $this->belongsTo(Mpasien::class, 'norm', 'rs1');
    }
}
