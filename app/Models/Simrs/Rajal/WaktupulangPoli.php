<?php

namespace App\Models\Simrs\Rajal;

use App\Models\Poli;
use App\Models\Simrs\Master\Mpasien;
use App\Models\Simrs\Pendaftaran\Rajalumum\Bpjs_http_respon;
use App\Models\Simrs\Penunjang\Kamaroperasi\JadwaloperasiController;
use App\Models\Simrs\Planing\Simpanspri;
use App\Models\Simrs\Planing\Simpansuratkontrol;
use App\Models\Simrs\Planing\Transrujukan;
use App\Models\Simrs\Ranap\Mruangranap;
use App\Models\Simrs\Rekom\Rekomdpjp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaktupulangPoli extends Model
{
    use HasFactory;
    protected $table = 'rs141';
    protected $guarded = ['id'];

    public function masterpoli()
    {
        return $this->hasOne(Poli::class, 'rs1', 'rs3');
    }

    public function masterpasien()
    {
        return $this->hasOne(Mpasien::class, 'rs1', 'rs2');
    }
    public function rekomdpjp()
    {
        return $this->hasOne(Rekomdpjp::class, 'noreg', 'rs1');
    }
    public function listkonsul()
    {
        return $this->hasOne(Listkonsulantarpoli::class, 'noreg_lama', 'rs1');
    }
    public function transrujukan()
    {
        return $this->hasOne(Transrujukan::class, 'rs1', 'rs1');
    }
    public function spri()
    {
        return $this->hasOne(Simpanspri::class, 'noreg', 'rs1');
    }
    public function kontrol()
    {
        return $this->hasOne(Simpansuratkontrol::class, 'noreg', 'rs1');
    }
    public function ranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs4', 'rs5');
    }
    public function operasi()
    {
        return $this->hasOne(JadwaloperasiController::class, 'noreg', 'rs1');
    }
    public function bpjsresponse()
    {
        // return $this->hasMany(Bpjs_http_respon::class, 'noreg', 'rs1');
        return $this->hasOne(Bpjs_http_respon::class, 'noreg', 'rs1');
    }
}
