<?php

namespace App\Models\Simrs\Master;

use App\Models\Simrs\Ews\MapingProcedure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mtindakan extends Model
{
    use HasFactory;
    protected $table = 'rs30';
    protected $guarded = ['id'];
    // public $primarykey = 'rs1';
    // protected $keyType = 'string';

    public function maapingprocedure()
    {
        return $this->hasOne(MapingProcedure::class, 'kdMaster', 'rs1');
    }

    public function snowmed()
    {
       return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'kode');
    }
    public function snowmedx()
    {
       return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
    }

    public function icd()
    {
       return $this->hasOne(Icd9prosedure::class, 'kd_prosedur', 'icd9');
    }
}
