<?php

namespace App\Models\Simrs\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpemeriksaanlab extends Model
{
    use HasFactory;
    protected $table = 'rs49';
    protected $guarded = ['id'];
    public $timestamps = false;


    public function spesimen()
    {
       return $this->hasOne(MspesimenLab::class, 'rs1', 'rs1');
    }

    public function loinclab()
    {
       return $this->hasMany(MloincLab::class, 'code', 'loinc');
    }
}
