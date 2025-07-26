<?php

namespace App\Models\Simrs\Penunjang\Ambulan;

use App\Models\Simrs\Master\Mnakes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReqAmbulan extends Model
{
    use HasFactory;
    protected $table = 'rs276';
    protected $guarded = ['id'];

    public function tujuan()
    {
        return $this->hasOne(TujuanAmbulan::class, 'rs1','rs10');
    }

    public function perawat()
    {
        return $this->hasOne(Mnakes::class, 'rs1','rs13');
    }
    public function perawat2()
    {
        return $this->hasOne(Mnakes::class, 'rs1','rs14');
    }
}
