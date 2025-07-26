<?php

namespace App\Models\Simrs\Pemeriksaanfisik;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pemeriksaan_Psikologoldll extends Model
{
    use HasFactory;
    protected $table = 'pemeriksaan_psikologisdll';
    protected $guarded = ['id'];
}
