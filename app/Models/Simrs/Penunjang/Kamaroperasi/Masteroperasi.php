<?php

namespace App\Models\Simrs\Penunjang\Kamaroperasi;

use App\Models\Simrs\Master\MappingSnowmed;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Masteroperasi extends Model
{
    use HasFactory;
    protected $table = 'rs53';
    protected $guarded = ['id'];

    public function snowmed()
    {
       return $this->hasMany(MappingSnowmed::class, 'kdMaster', 'rs1');
    }
}
