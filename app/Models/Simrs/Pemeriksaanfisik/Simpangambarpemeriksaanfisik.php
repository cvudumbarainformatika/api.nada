<?php

namespace App\Models\Simrs\Pemeriksaanfisik;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simpangambarpemeriksaanfisik extends Model
{
    use HasFactory;
    protected $table = 'pemeriksaan_fisik_gb';
    protected $guarded = ['id'];
}
