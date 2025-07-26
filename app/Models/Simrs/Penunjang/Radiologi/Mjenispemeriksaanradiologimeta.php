<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mjenispemeriksaanradiologimeta extends Model
{
    use HasFactory;
    protected $table = 'mjenispemeriksaan_radiologi';
    protected $guarded = ['id'];
}
