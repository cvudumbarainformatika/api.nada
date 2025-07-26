<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use App\Models\Simrs\Master\MappingSnowmed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpemeriksaanradiologi extends Model
{
    use HasFactory;
    protected $table = 'rs47';
    protected $gurded = ['id1'];
    public $timestamps = false;
    //protected $primaryKey = 'rs1';


    public function snowmed()
    {
       return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
    }
}
