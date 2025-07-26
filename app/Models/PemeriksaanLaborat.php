<?php

namespace App\Models;

use App\Models\Simrs\Master\MspesimenLab;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemeriksaanLaborat extends Model
{
    use HasFactory;

    protected $table = 'rs49';
    protected $guarded = ['id'];
    public $timestamps = false;

    public function transaksi_laborat()
    {
        return $this->hasMany(TransaksiLaborat::class, 'rs1');
    }
    public function transaksi_laborat_luar()
    {
        return $this->hasMany(LaboratLuar::class, 'kd_lab', 'rs1');
    }

    public function spesimen()
    {
       return $this->hasOne(MspesimenLab::class, 'rs1', 'rs1');
    }

    // public function paket_laborats(){
    //     return $this->hasMany(self::class, 'rs21');
    // }

}
