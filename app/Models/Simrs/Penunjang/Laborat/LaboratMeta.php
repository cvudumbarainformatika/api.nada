<?php

namespace App\Models\Simrs\Penunjang\Laborat;

use App\Models\Simrs\Master\Mpoli;
use App\Models\Simrs\Ranap\Mruangranap;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaboratMeta extends Model
{
    use HasFactory;
    protected $table = 'rs51_meta';
    protected $guarded = ['id'];

    public function details()
    {
        return $this->hasMany(Laboratpemeriksaan::class, 'rs2', 'nota');
    }
    public function poli()
    {
        return $this->hasOne(Mpoli::class, 'rs1', 'unit_pengirim');
    }
    public function ranap()
    {
        return $this->hasOne(Mruangranap::class, 'rs4', 'unit_pengirim');
    }
}
