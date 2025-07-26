<?php

namespace App\Models\Simrs\Penunjang\Radiologi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mpemeriksaanradiologimeta extends Model
{
    use HasFactory;
    protected $table = 'mpemeriksaan_radiologi';
    protected $guarded = ['id'];
}
