<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresep;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Permintaanresepracikan;
use App\Models\Simrs\Penunjang\Farmasinew\Depo\Resepkeluarheder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transpermintaanradiologi extends Model
{
    use HasFactory;
    protected $table = 'rs106';
    protected $guarded = ['id'];
    // public $timestamps = false;
    // protected $primaryKey = 'rs1';
    //   protected $keyType = 'string';

    public function reltransrinci()
    {
        return  $this->hasMany(Transradiologi::class, 'rs1', 'rs1');
    }

    // ini berdasarkan nota
    public function rincians()
    {
        return  $this->hasMany(Transradiologi::class, 'rs2', 'rs2');
    }

    public function newapotekrajal()
    {
        return $this->hasMany(Resepkeluarheder::class, 'noreg', 'rs1');
    }
}
