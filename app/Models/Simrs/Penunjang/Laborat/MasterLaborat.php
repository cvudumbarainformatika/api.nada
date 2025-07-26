<?php

namespace App\Models\Simrs\Penunjang\Laborat;

use App\Models\Simrs\Master\MappingSnowmed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterLaborat extends Model
{
    use HasFactory;
    protected $table = 'rs49';
    protected $guarded = ['id'];

    public function snowmed()
    {
       return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
    }
}
